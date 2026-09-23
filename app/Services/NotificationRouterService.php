<?php

namespace App\Services;

use App\Mail\DocumentMail;
use App\Models\NotificationDelivery;
use App\Models\NotificationRoute;
use App\Models\Role;
use App\Models\User;
use App\Notifications\SystemAlert;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use InvalidArgumentException;

class NotificationRouterService
{
    public function __construct(private readonly DocumentIdentityService $identity)
    {
    }

    /**
     * Fire a catalogued event through the routing matrix (10.13).
     * Bell notifications land instantly; email queues; SMS queues via the
     * delivery log (gateway integration lands in Phase 5).
     *
     * @param  array  $payload  ['title' => …, 'message' => …, 'url' => route to the record]
     */
    public function fire(string $eventKey, array $payload, ?int $branchId = null): void
    {
        $catalogue = config('notification_events', []);

        if (! array_key_exists($eventKey, $catalogue)) {
            throw new InvalidArgumentException("Unknown notification event [{$eventKey}].");
        }

        $event = $catalogue[$eventKey];

        $routes = NotificationRoute::where('event_key', $eventKey)
            ->where(fn ($q) => $branchId === null
                ? $q->whereNull('branch_id')
                : $q->where(fn ($q2) => $q2->whereNull('branch_id')->orWhere('branch_id', $branchId)))
            ->get();

        // Nothing vanishes silently: unrouted events still reach the log.
        if ($routes->isEmpty()) {
            Log::info('notifications.unrouted_event', [
                'event' => $eventKey, 'branch' => $branchId, 'title' => $payload['title'] ?? null,
            ]);

            return;
        }

        foreach ($routes as $route) {
            foreach ($this->resolveUsers($route) as $user) {
                match ($route->channel) {
                    'bell' => $user->notify(new SystemAlert($eventKey, $event, $payload)),
                    'email' => $this->sendEmail($eventKey, $event, $payload, $user),
                    'sms' => $this->logSms($eventKey, $payload, $user),
                    default => null,
                };
            }
        }
    }

    /** @return \Illuminate\Support\Collection<User> */
    private function resolveUsers(NotificationRoute $route)
    {
        if ($route->recipient_type === 'user') {
            return User::where('id', $route->recipient_id)->where('status', 'active')->get();
        }

        $role = Role::find($route->recipient_id);

        return $role
            ? $role->users()->where('status', 'active')->get()
            : collect();
    }

    private function sendEmail(string $eventKey, array $event, array $payload, User $user): void
    {
        $delivery = NotificationDelivery::create([
            'event_key' => $eventKey,
            'channel' => 'email',
            'recipient' => $user->email,
            'subject' => $payload['title'] ?? $event['label'],
            'status' => 'queued',
        ]);

        try {
            Mail::to($user->email)->queue(new DocumentMail(
                subjectLine: $payload['title'] ?? $event['label'],
                heading: $payload['title'] ?? $event['label'],
                bodyText: $payload['message'] ?? '',
                identity: $this->identity->for(),
            ));

            $delivery->update(['status' => 'sent', 'sent_at' => now(), 'attempts' => 1]);
        } catch (\Throwable $e) {
            $delivery->update(['status' => 'failed', 'error' => $e->getMessage(), 'attempts' => 1]);
            Log::error('notifications.email_failed', ['event' => $eventKey, 'to' => $user->email, 'error' => $e->getMessage()]);
        }
    }

    private function logSms(string $eventKey, array $payload, User $user): void
    {
        // SMS gateway integration lands in Phase 5 (job-complete SMS);
        // until then, SMS routes are recorded so nothing is lost.
        NotificationDelivery::create([
            'event_key' => $eventKey,
            'channel' => 'sms',
            'recipient' => $user->email,
            'subject' => $payload['title'] ?? null,
            'status' => 'queued',
        ]);
    }
}
