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
use App\Models\SalesDocumentLine;
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
 * A part with no price on any applicable list may be sold at a price the cashier
 * enters at the till — never below cost, always recorded. A priced part still
 * reprices server-side (a manual price can't override the list).
 */
class PosManualPriceTest extends TestCase
{
    use RefreshDatabase;

    private User $cashier;

    private Customer $walkIn;

    private Branch $branch;

    private Part $unpriced;   // opening stock at cost 60, no price list entry

    private Part $priced;     // list price 100, cost 3.20

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();

        (new ChartOfAccountsSeeder)->run();
        (new FinancialPeriodSeeder)->run();
        (new NumberSequenceSeeder)->run();
        Role::create(['name' => 'Cashier', 'description' => 'Cashier']);
        (new SystemRouteSeeder)->run();
        (new RolePermissionSeeder)->run();

        $this->branch = Branch::factory()->create(['is_main_branch' => true]);
        $ledger = app(StockLedgerService::class);

        $this->unpriced = Part::factory()->create(['part_number' => 'B1170']);
        $ledger->post($this->unpriced->id, $this->branch, 'OPENING_BALANCE', 10, 60.00, 'Test', 1);

        $this->priced = Part::factory()->create(['part_number' => 'Z762']);
        $ledger->post($this->priced->id, $this->branch, 'OPENING_BALANCE', 100, 3.20, 'Test', 1);

        $retail = PriceList::create(['name' => 'Standard Retail', 'type' => 'retail', 'is_default' => true]);
        PriceListItem::create(['price_list_id' => $retail->id, 'part_id' => $this->priced->id, 'price' => 100.00]);

        $this->walkIn = Customer::create([
            'customer_number' => 'WALK-IN', 'type' => 'cash', 'name' => 'Cash Customer',
            'is_walk_in' => true, 'price_list_id' => $retail->id, 'credit_limit' => 0,
        ]);

        $this->cashier = User::factory()->create(['username' => 'till1', 'password' => Hash::make('secret-till'), 'password_changed_at' => now()]);
        $this->cashier->roles()->attach(Role::where('name', 'Cashier')->first()->id);
    }

    private function sale(array $lines, float $amount): array
    {
        return [
            'customer_id' => $this->walkIn->id,
            'lines' => $lines,
            'payments' => [['method' => 'cash', 'amount' => $amount, 'tendered' => $amount]],
        ];
    }

    public function test_unpriced_part_sells_at_a_manual_price_and_is_audited(): void
    {
        // Manual selling price 80 excl → VAT 15% → 92.00 incl.
        $this->actingAs($this->cashier)
            ->post(route('sales.pos.store'), $this->sale([
                ['part_id' => $this->unpriced->id, 'qty' => 1, 'discount_pct' => 0, 'manual_price' => 80.00],
            ], 92.00))
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $invoice = SalesDocument::where('document_type', 'invoice')->firstOrFail();
        $line = SalesDocumentLine::where('document_id', $invoice->id)->firstOrFail();
        $this->assertEquals(80.00, (float) $line->unit_price);

        $audit = AuditLog::where('action', 'sales.pos.manual_price')->firstOrFail();
        $this->assertSame('B1170', $audit->after['lines'][0]['part']);
        $this->assertEquals(80.00, $audit->after['lines'][0]['price']);
        $this->assertEquals(60.00, $audit->after['lines'][0]['cost']);
    }

    public function test_manual_price_below_cost_is_rejected(): void
    {
        // Cost is 60; 50 is below it.
        $this->actingAs($this->cashier)
            ->post(route('sales.pos.store'), $this->sale([
                ['part_id' => $this->unpriced->id, 'qty' => 1, 'discount_pct' => 0, 'manual_price' => 50.00],
            ], 57.50))
            ->assertSessionHasErrors('lines');

        $this->assertSame(0, SalesDocument::count());
    }

    public function test_unpriced_part_without_a_price_is_rejected(): void
    {
        $this->actingAs($this->cashier)
            ->post(route('sales.pos.store'), $this->sale([
                ['part_id' => $this->unpriced->id, 'qty' => 1, 'discount_pct' => 0],
            ], 10.00))
            ->assertSessionHasErrors('lines');

        $this->assertSame(0, SalesDocument::count());
    }

    public function test_manual_price_cannot_override_a_listed_price(): void
    {
        // Part has a list price of 100 excl (115 incl). A manual_price of 5 is ignored.
        $this->actingAs($this->cashier)
            ->post(route('sales.pos.store'), $this->sale([
                ['part_id' => $this->priced->id, 'qty' => 1, 'discount_pct' => 0, 'manual_price' => 5.00],
            ], 115.00))
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $line = SalesDocumentLine::firstOrFail();
        $this->assertEquals(100.00, (float) $line->unit_price);
        $this->assertSame(0, AuditLog::where('action', 'sales.pos.manual_price')->count());
    }
}
