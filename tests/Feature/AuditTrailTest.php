<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\Part;
use App\Models\PriceList;
use App\Models\PriceListItem;
use App\Models\Role;
use App\Models\User;
use App\Services\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 8.4 — rule 10: master-data edits, setting changes and permission
 * grants leave an append-only before/after trail with the acting user.
 */
class AuditTrailTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_credit_limit_change_records_before_and_after(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $customer = Customer::create([
            'customer_number' => 'CUST-1', 'type' => 'business', 'name' => 'Bochum Taxis',
            'is_walk_in' => false, 'credit_limit' => 1000,
        ]);
        $customer->update(['credit_limit' => 5000]);

        $log = AuditLog::where('action', 'customer.updated')->firstOrFail();
        $this->assertSame($user->id, $log->user_id);
        $this->assertSame('Bochum Taxis', $log->label);
        $this->assertEquals(1000, $log->before['credit_limit']);
        $this->assertEquals(5000, $log->after['credit_limit']);
        $this->assertArrayNotHasKey('name', $log->after, 'only changed fields are recorded');

        $this->assertSame(1, AuditLog::where('action', 'customer.created')->count());
    }

    public function test_price_change_and_part_edit_are_audited(): void
    {
        $this->actingAs(User::factory()->create());
        $part = Part::factory()->create(['part_number' => 'Z762']);
        $list = PriceList::create(['name' => 'Retail', 'type' => 'retail', 'is_default' => true]);
        $item = PriceListItem::create(['price_list_id' => $list->id, 'part_id' => $part->id, 'price' => 6.40]);

        $item->update(['price' => 7.90]);
        $part->update(['description' => 'Oil filter — Hilux']);

        $price = AuditLog::where('action', 'price_list_item.updated')->firstOrFail();
        $this->assertEquals(6.40, $price->before['price']);
        $this->assertEquals(7.90, $price->after['price']);

        $this->assertSame(1, AuditLog::where('action', 'part.updated')->where('label', 'Z762')->count());
        $this->assertSame(0, AuditLog::where('action', 'part.created')->count(), 'bulk-created parts do not flood the stream');
    }

    public function test_setting_change_is_audited_with_old_and_new_value(): void
    {
        $user = User::factory()->create();
        $settings = app(SettingsService::class);

        $settings->set('sales.max_discount_without_approval', 15, null, $user->id);

        $log = AuditLog::where('action', 'setting.changed')->firstOrFail();
        $this->assertSame($user->id, $log->user_id);
        $this->assertSame('sales.max_discount_without_approval', $log->after['key']);
        $this->assertEquals(15, $log->after['value']);
        $this->assertNotEquals(15, $log->before['value']);
    }

    public function test_password_is_never_written_to_the_audit_stream(): void
    {
        $user = User::factory()->create();
        $user->update(['password' => 'new-hash', 'name' => 'Renamed']);

        $log = AuditLog::where('action', 'user.updated')->firstOrFail();
        $this->assertSame('••••••', $log->after['password']);
        $this->assertSame('Renamed', $log->after['name']);
    }

    public function test_role_permission_sync_is_audited(): void
    {
        $admin = User::factory()->create(['password_changed_at' => now()]);
        $super = Role::create(['name' => 'Superuser', 'description' => 'All']);
        $admin->roles()->attach($super->id);
        $role = Role::create(['name' => 'Cashier', 'description' => 'Till']);
        (new \Database\Seeders\SystemRouteSeeder)->run();
        $routeId = \App\Models\SystemRoute::where('name', 'sales.pos')->value('id');

        $this->actingAs($admin)->patch(route('auth.roles.update', $role), [
            'name' => 'Cashier', 'description' => 'Till', 'route_ids' => [$routeId],
        ])->assertRedirect();

        $log = AuditLog::where('action', 'role.permissions_changed')->firstOrFail();
        $this->assertSame([], $log->before['routes']);
        $this->assertSame(['sales.pos'], $log->after['routes']);
    }
}
