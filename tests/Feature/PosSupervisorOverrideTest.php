<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Part;
use App\Models\PriceList;
use App\Models\PriceListItem;
use App\Models\Role;
use App\Models\SalesDocument;
use App\Models\User;
use App\Services\StockLedgerService;
use Database\Seeders\ChartOfAccountsSeeder;
use Database\Seeders\FinancialPeriodSeeder;
use Database\Seeders\NumberSequenceSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SystemRouteSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Phase 8.3 — an over-limit till discount needs a supervisor's own credentials,
 * verified server-side, recorded on the invoice and in the audit trail.
 */
class PosSupervisorOverrideTest extends TestCase
{
    use RefreshDatabase;

    private User $cashier;

    private User $manager;

    private Customer $walkIn;

    private Part $part;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();

        (new ChartOfAccountsSeeder)->run();
        (new FinancialPeriodSeeder)->run();
        (new NumberSequenceSeeder)->run();
        foreach (['Cashier', 'Manager'] as $name) {
            Role::create(['name' => $name, 'description' => $name]);
        }
        (new SystemRouteSeeder)->run();
        (new RolePermissionSeeder)->run();

        $branch = Branch::factory()->create(['is_main_branch' => true]);
        $this->part = Part::factory()->create(['part_number' => 'Z762']);
        app(StockLedgerService::class)->post($this->part->id, $branch, 'OPENING_BALANCE', 100, 3.20, 'Test', 1);

        $retail = PriceList::create(['name' => 'Standard Retail', 'type' => 'retail', 'is_default' => true]);
        PriceListItem::create(['price_list_id' => $retail->id, 'part_id' => $this->part->id, 'price' => 100.00]);
        $this->walkIn = Customer::create([
            'customer_number' => 'WALK-IN', 'type' => 'cash', 'name' => 'Cash Customer',
            'is_walk_in' => true, 'price_list_id' => $retail->id, 'credit_limit' => 0,
        ]);

        $this->cashier = User::factory()->create(['username' => 'till1', 'password' => Hash::make('secret-till'), 'password_changed_at' => now()]);
        $this->cashier->roles()->attach(Role::where('name', 'Cashier')->first()->id);
        $this->manager = User::factory()->create(['username' => 'boss', 'password' => Hash::make('secret-boss'), 'password_changed_at' => now()]);
        $this->manager->roles()->attach(Role::where('name', 'Manager')->first()->id);
    }

    private function sale(array $extra = []): array
    {
        return $extra + [
            'customer_id' => $this->walkIn->id,
            'lines' => [['part_id' => $this->part->id, 'qty' => 1, 'discount_pct' => 50]],
            'payments' => [['method' => 'cash', 'amount' => 57.50, 'tendered' => 60]],
        ];
    }

    public function test_over_limit_discount_without_a_supervisor_is_rejected(): void
    {
        $this->actingAs($this->cashier)
            ->post(route('sales.pos.store'), $this->sale())
            ->assertSessionHasErrors('discount');

        $this->assertSame(0, SalesDocument::count());
    }

    public function test_another_cashier_cannot_approve(): void
    {
        $other = User::factory()->create(['username' => 'till2', 'password' => Hash::make('secret-two'), 'password_changed_at' => now()]);
        $other->roles()->attach(Role::where('name', 'Cashier')->first()->id);

        $this->actingAs($this->cashier)
            ->post(route('sales.pos.store'), $this->sale(['supervisor_username' => 'till2', 'supervisor_password' => 'secret-two']))
            ->assertSessionHasErrors('discount');

        $this->assertSame(0, SalesDocument::count());
    }

    public function test_wrong_supervisor_password_is_rejected_and_logged_nowhere_as_approved(): void
    {
        $this->actingAs($this->cashier)
            ->post(route('sales.pos.store'), $this->sale(['supervisor_username' => 'boss', 'supervisor_password' => 'nope']))
            ->assertSessionHasErrors('discount');

        $this->assertSame(0, AuditLog::where('action', 'sales.discount_override')->count());
    }

    public function test_manager_credentials_approve_record_the_approver_and_audit_the_override(): void
    {
        $this->actingAs($this->cashier)
            ->post(route('sales.pos.store'), $this->sale(['supervisor_username' => 'boss', 'supervisor_password' => 'secret-boss']))
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $invoice = SalesDocument::where('document_type', 'invoice')->firstOrFail();
        $this->assertSame($this->manager->id, $invoice->discount_approved_by);
        $this->assertSame($this->cashier->id, $invoice->salesperson_id);
        $this->assertEquals(57.50, (float) $invoice->total_incl);

        $audit = AuditLog::where('action', 'sales.discount_override')->firstOrFail();
        $this->assertSame($this->cashier->id, $audit->user_id);
        $this->assertSame($this->manager->id, $audit->after['approved_by']);
        $this->assertEquals(50, $audit->after['lines'][0]['discount_pct']);
    }

    public function test_approve_endpoint_pre_checks_credentials(): void
    {
        $this->actingAs($this->cashier)
            ->postJson(route('sales.pos.verify-supervisor'), ['username' => 'boss', 'password' => 'secret-boss'])
            ->assertOk()->assertJson(['ok' => true, 'approver' => $this->manager->name]);

        $this->actingAs($this->cashier)
            ->postJson(route('sales.pos.verify-supervisor'), ['username' => 'boss', 'password' => 'wrong'])
            ->assertStatus(422)->assertJson(['ok' => false]);
    }

    public function test_exact_lookup_adds_a_scanned_part_number(): void
    {
        $this->actingAs($this->cashier)
            ->getJson(route('sales.pos.part-lookup', ['q' => 'Z762', 'exact' => 1]))
            ->assertOk()->assertJsonPath('parts.0.part_number', 'Z762')->assertJsonCount(1, 'parts');

        $this->actingAs($this->cashier)
            ->getJson(route('sales.pos.part-lookup', ['q' => 'Z76', 'exact' => 1]))
            ->assertOk()->assertJsonCount(0, 'parts');
    }
}
