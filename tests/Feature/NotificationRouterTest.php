<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\NotificationDelivery;
use App\Models\NotificationRoute;
use App\Models\Role;
use App\Models\User;
use App\Services\NotificationRouterService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use InvalidArgumentException;
use Tests\TestCase;

class NotificationRouterTest extends TestCase
{
    use RefreshDatabase;

    private NotificationRouterService $router;

    private Role $buyerRole;

    private User $buyer;

    protected function setUp(): void
    {
        parent::setUp();

        Company::factory()->create();
        $this->router = app(NotificationRouterService::class);
        $this->buyerRole = Role::create(['name' => 'Buyer', 'description' => 'Purchasing']);
        $this->buyer = User::factory()->create(['password_changed_at' => now()]);
        $this->buyer->roles()->attach($this->buyerRole->id);
    }

    public function test_bell_route_delivers_database_notification_to_role_members(): void
    {
        NotificationRoute::create([
            'event_key' => 'inventory.stock_below_reorder',
            'channel' => 'bell',
            'recipient_type' => 'role',
            'recipient_id' => $this->buyerRole->id,
        ]);

        $this->router->fire('inventory.stock_below_reorder', [
            'title' => '3 parts below reorder point',
            'message' => 'Oil filters and brake pads need ordering.',
            'url' => '/inventory/reorder',
        ]);

        $this->assertSame(1, $this->buyer->notifications()->count());
        $data = $this->buyer->notifications()->first()->data;
        $this->assertSame('3 parts below reorder point', $data['title']);
        $this->assertSame('warning', $data['severity']);
    }

    public function test_email_route_logs_delivery_and_queues_mail(): void
    {
        Mail::fake();

        NotificationRoute::create([
            'event_key' => 'system.backup_failed',
            'channel' => 'email',
            'recipient_type' => 'user',
            'recipient_id' => $this->buyer->id,
        ]);

        $this->router->fire('system.backup_failed', [
            'title' => 'Backup failed last night',
            'message' => 'The 22:00 backup did not complete.',
        ]);

        Mail::assertQueued(\App\Mail\DocumentMail::class);
        $this->assertDatabaseHas('notification_deliveries', [
            'event_key' => 'system.backup_failed',
            'channel' => 'email',
            'recipient' => $this->buyer->email,
            'status' => 'sent',
        ]);
    }

    public function test_unrouted_event_logs_instead_of_vanishing(): void
    {
        Log::shouldReceive('info')->once()->withArgs(
            fn ($msg, $ctx) => $msg === 'notifications.unrouted_event'
                && $ctx['event'] === 'purchasing.po_overdue'
        );

        $this->router->fire('purchasing.po_overdue', ['title' => 'PO late']);
    }

    public function test_unknown_event_key_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->router->fire('nonsense.event', []);
    }

    public function test_branch_scoped_route_only_fires_for_that_branch(): void
    {
        $branch = \App\Models\Branch::factory()->create();
        $other = \App\Models\Branch::factory()->create();

        NotificationRoute::create([
            'event_key' => 'inventory.stock_below_reorder',
            'branch_id' => $branch->id,
            'channel' => 'bell',
            'recipient_type' => 'role',
            'recipient_id' => $this->buyerRole->id,
        ]);

        $this->router->fire('inventory.stock_below_reorder', ['title' => 'Other branch'], $other->id);
        $this->assertSame(0, $this->buyer->notifications()->count());

        $this->router->fire('inventory.stock_below_reorder', ['title' => 'Right branch'], $branch->id);
        $this->assertSame(1, $this->buyer->notifications()->count());
    }

    public function test_inactive_users_receive_nothing(): void
    {
        $this->buyer->update(['status' => 'inactive']);

        NotificationRoute::create([
            'event_key' => 'inventory.stock_below_reorder',
            'channel' => 'bell',
            'recipient_type' => 'role',
            'recipient_id' => $this->buyerRole->id,
        ]);

        $this->router->fire('inventory.stock_below_reorder', ['title' => 'x']);

        $this->assertSame(0, $this->buyer->notifications()->count());
    }

    public function test_sms_route_is_recorded_pending_gateway(): void
    {
        NotificationRoute::create([
            'event_key' => 'workshop.job_completed',
            'channel' => 'sms',
            'recipient_type' => 'user',
            'recipient_id' => $this->buyer->id,
        ]);

        $this->router->fire('workshop.job_completed', ['title' => 'Vehicle ready']);

        $this->assertDatabaseHas('notification_deliveries', [
            'event_key' => 'workshop.job_completed',
            'channel' => 'sms',
            'status' => 'queued',
        ]);
        $this->assertSame(0, NotificationDelivery::where('channel', 'sms')->where('status', 'sent')->count());
    }
}
