<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class SystemAlert extends Notification
{
    use Queueable;

    public function __construct(
        private readonly string $eventKey,
        private readonly array $event,
        private readonly array $payload,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'event_key' => $this->eventKey,
            'severity' => $this->event['severity'] ?? 'info',
            'title' => $this->payload['title'] ?? $this->event['label'],
            'message' => $this->payload['message'] ?? null,
            'url' => $this->payload['url'] ?? null,
        ];
    }
}
