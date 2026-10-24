<?php

namespace Tests\Feature;

use App\Models\BinLocation;
use App\Models\Branch;
use App\Models\Part;
use App\Models\PurchaseOrder;
use App\Models\Role;
use App\Models\Supplier;
use App\Models\SupplierReturn;
use App\Models\User;
use App\Services\GrnPostingService;
use App\Services\StockLedgerService;
use Database\Seeders\ChartOfAccountsSeeder;
use Database\Seeders\FinancialPeriodSeeder;
use Database\Seeders\NumberSequenceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Every Purchasing-hub screen renders with the props its page expects —
 * stat chips, bin pre-fill, branch-aware part lookup.
 */
class PurchasingScreensSmokeTest extends TestCase
{
    use RefreshDatabase;

    private Branch $branch;

    private Supplier $supplier;

    private Part $part;

    private User $user;

    private PurchaseOrder $po;

    private $grn;

    protected function setUp(): void
    {
        parent::setUp();

        (new ChartOfAccountsSeeder)->run();
        (new FinancialPeriodSeeder)->run();
        (new NumberSequenceSeeder)->run();

        $this->branch = Branch::factory()->create();
        $bin = BinLocation::create(['branch_id' => $this->branch->id, 'code' => 'A-01-01', 'is_active' => true]);
        $this->supplier = Supplier::create([
            'supplier_number' => 'SUPP-00001', 'name' => 'GUD Distributors',
            'payment_terms_days' => 30,
        ]);
        $this->part = Part::factory()->create(['part_number' => 'Z762', 'description' => 'Oil Filter']);

        app(StockLedgerService::class)->post($this->part->id, $this->branch, 'OPENING_BALANCE', 10, 3.40, 'Test', 1);
        $this->part->stockLevels()->update(['bin_location_id' => $bin->id]);

        $this->po = PurchaseOrder::create([
            'po_number' => 'PO-TEST-01', 'supplier_id' => $this->supplier->id,
            'branch_id' => $this->branch->id, 'status' => 'confirmed',
            'order_date' => now()->toDateString(),
            'expected_date' => now()->subDays(2)->toDateString(),
            'subtotal' => 68.0, 'total' => 68.0,
        ]);
        $this->po->lines()->create([
            'part_id' => $this->part->id, 'description' => 'Oil Filter',
            'qty_ordered' => 20, 'unit_cost' => 3.40, 'line_total' => 68.0,
        ]);

        $partialPo = PurchaseOrder::create([
            'po_number' => 'PO-TEST-02', 'supplier_id' => $this->supplier->id,
            'branch_id' => $this->branch->id, 'status' => 'submitted',
            'order_date' => now()->toDateString(), 'subtotal' => 34.0, 'total' => 34.0,
        ]);
        $partialPo->lines()->create([
            'part_id' => $this->part->id, 'description' => 'Oil Filter',
            'qty_ordered' => 10, 'unit_cost' => 3.40, 'line_total' => 34.0,
        ]);
        $this->grn = app(GrnPostingService::class)->receive($partialPo, [
            ['po_line_id' => $partialPo->lines->first()->id, 'qty_received' => 5],
        ]);

        $role = Role::create(['name' => 'Superuser', 'description' => 'Superuser']);
        $this->user = User::factory()->create(['password_changed_at' => now()]);
        $this->user->roles()->attach($role->id, ['created_at' => now(), 'updated_at' => now()]);
    }

    public function test_orders_index_stats_and_overdue_flag(): void
    {
        $this->actingAs($this->user)->get(route('purchasing.orders.index'))
            ->assertInertia(fn (Assert $p) => $p->component('Purchasing/Orders/Index')
                ->where('stats.open', 2)
                ->where('stats.overdue', 1)
                ->where('orders.data.0.is_overdue', fn ($v) => is_bool($v)));
    }

    public function test_orders_create_and_show(): void
    {
        $this->actingAs($this->user)->get(route('purchasing.orders.create', ['supplier_id' => $this->supplier->id]))
            ->assertInertia(fn (Assert $p) => $p->component('Purchasing/Orders/Create')
                ->where('preselectedSupplierId', $this->supplier->id)
                ->has('suppliers.0.lead_time_days'));

        $this->actingAs($this->user)->get(route('purchasing.orders.show', $this->po))
            ->assertInertia(fn (Assert $p) => $p->component('Purchasing/Orders/Show')
                ->where('order.is_receivable', true));
    }

    public function test_part_lookup_returns_branch_stock_and_bin(): void
    {
        $json = $this->actingAs($this->user)->getJson(
            route('purchasing.orders.part-lookup').'?q=Z762&branch_id='.$this->branch->id
        )->assertOk()->json('parts.0');

        $this->assertEquals(15.0, $json['on_hand']); // 10 opening + 5 received
        $this->assertSame('A-01-01', $json['bin']);
    }

    public function test_grns_index_and_create_prefills_primary_bin(): void
    {
        $this->actingAs($this->user)->get(route('purchasing.grns.index'))
            ->assertInertia(fn (Assert $p) => $p->component('Purchasing/GRNs/Index')
                ->where('stats.awaiting', 2)
                ->has('awaiting.0.is_overdue'));

        $this->actingAs($this->user)->get(route('purchasing.grns.create', $this->po))
            ->assertInertia(fn (Assert $p) => $p->component('Purchasing/GRNs/Create')
                ->has('order.lines.0.suggested_bin_id')
                ->where('order.lines.0.suggested_bin_id', fn ($v) => $v === BinLocation::first()->id));
    }

    public function test_invoices_index_stats_and_create_props(): void
    {
        $this->actingAs($this->user)->get(route('purchasing.invoices.index'))
            ->assertInertia(fn (Assert $p) => $p->component('Purchasing/Invoices/Index')
                ->where('stats.awaiting_capture', 1)
                ->has('uninvoicedGrns.0'));

        $this->actingAs($this->user)->get(route('purchasing.invoices.create', $this->grn))
            ->assertInertia(fn (Assert $p) => $p->component('Purchasing/Invoices/Create')
                ->has('vatRate')
                ->where('grn.supplier_terms_days', 30));
    }

    public function test_returns_and_credits_index(): void
    {
        SupplierReturn::create([
            'return_number' => 'SR-TEST-01', 'supplier_id' => $this->supplier->id,
            'branch_id' => $this->branch->id, 'reason' => 'damaged', 'status' => 'shipped',
        ]);

        $this->actingAs($this->user)->get(route('purchasing.returns.index'))
            ->assertInertia(fn (Assert $p) => $p->component('Purchasing/Returns/Index')
                ->where('stats.awaiting_credit', 1));

        $this->actingAs($this->user)->get(route('purchasing.returns.create'))
            ->assertInertia(fn (Assert $p) => $p->component('Purchasing/Returns/Create'));

        $this->actingAs($this->user)->get(route('purchasing.supplier-credits.index'))
            ->assertInertia(fn (Assert $p) => $p->component('Purchasing/SupplierCredits/Index')
                ->has('stats'));
    }

    public function test_imports_and_price_comparison(): void
    {
        \App\Models\ImportShipment::create([
            'shipment_ref' => 'CN-TEST-01', 'supplier_id' => $this->supplier->id,
            'status' => 'in_transit', 'goods_value' => 5000,
        ]);

        $this->actingAs($this->user)->get(route('purchasing.imports.index'))
            ->assertInertia(fn (Assert $p) => $p->component('Purchasing/Imports/Index')
                ->where('stats.active', 1));

        $this->actingAs($this->user)->get(route('purchasing.price-comparison.index'))
            ->assertInertia(fn (Assert $p) => $p->component('Purchasing/PriceComparison/Index'));
    }
}
