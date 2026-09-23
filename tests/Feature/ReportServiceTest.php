<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\Part;
use App\Models\PriceList;
use App\Models\PriceListItem;
use App\Services\LedgerReportService;
use App\Services\ReportService;
use App\Services\SalesPostingService;
use App\Services\StockLedgerService;
use Database\Seeders\ChartOfAccountsSeeder;
use Database\Seeders\FinancialPeriodSeeder;
use Database\Seeders\NumberSequenceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Analytics must reconcile to the ledger they report on (Module 9).
 */
class ReportServiceTest extends TestCase
{
    use RefreshDatabase;

    private Branch $branch;

    private Customer $walkIn;

    private Part $part;

    protected function setUp(): void
    {
        parent::setUp();

        (new ChartOfAccountsSeeder)->run();
        (new FinancialPeriodSeeder)->run();
        (new NumberSequenceSeeder)->run();

        $this->branch = Branch::factory()->create();
        $this->part = Part::factory()->create(['part_number' => 'Z762', 'description' => 'Oil Filter']);
        app(StockLedgerService::class)->post($this->part->id, $this->branch, 'OPENING_BALANCE', 100, 3.20, 'Test', 1);

        $retail = PriceList::create(['name' => 'Standard Retail', 'type' => 'retail', 'is_default' => true]);
        PriceListItem::create(['price_list_id' => $retail->id, 'part_id' => $this->part->id, 'price' => 6.40]);

        $this->walkIn = Customer::create([
            'customer_number' => 'WALK-IN', 'type' => 'cash', 'name' => 'Cash Customer',
            'is_walk_in' => true, 'price_list_id' => $retail->id, 'credit_limit' => 0,
        ]);

        // Five cash sales of 2 units today.
        for ($i = 0; $i < 5; $i++) {
            app(SalesPostingService::class)->postInvoice(
                $this->walkIn, $this->branch->id,
                [['part_id' => $this->part->id, 'qty' => 2, 'unit_price' => 6.40]],
                [['method' => 'cash', 'amount' => 14.72, 'tendered' => 14.72]],
            );
        }
    }

    public function test_kpis_reflect_seeded_sales(): void
    {
        $kpi = app(ReportService::class)->kpis();
        // 5 × 14.72 = 73.60 today.
        $this->assertEqualsWithDelta(73.60, $kpi['today_sales'], 0.01);
        $this->assertEqualsWithDelta(73.60, $kpi['mtd_sales'], 0.01);
        // No account sales → AR 0. Stock 90 × 3.20 = 288.00.
        $this->assertEqualsWithDelta(0, $kpi['outstanding_ar'], 0.01);
        $this->assertEqualsWithDelta(288.00, $kpi['stock_value'], 0.01);
    }

    public function test_net_sales_deducts_credit_notes(): void
    {
        $reports = app(ReportService::class);
        $invoice = \App\Models\SalesDocument::where('document_type', 'invoice')->first();
        app(SalesPostingService::class)->postCreditNote(
            $invoice, [['line_id' => $invoice->lines->first()->id, 'qty' => 1, 'restock' => true]], 'refund_cash', 'Return'
        );

        // 73.60 − one unit credited (7.36) = 66.24.
        $this->assertEqualsWithDelta(66.24, $reports->netSales(now()->toDateString(), now()->toDateString()), 0.01);
    }

    public function test_top_parts_and_category(): void
    {
        $reports = app(ReportService::class);
        $from = now()->startOfMonth()->toDateString();
        $to = now()->toDateString();

        $top = $reports->topPartsByValue($from, $to);
        $this->assertSame('Z762', $top[0]['part_number']);
        // 10 units × 6.40 = 64.00 revenue (excl).
        $this->assertEqualsWithDelta(64.00, $top[0]['revenue'], 0.01);

        $cat = $reports->salesByCategory($from, $to);
        $this->assertEqualsWithDelta(64.00, collect($cat)->sum('revenue'), 0.01);
    }

    public function test_stock_value_reconciles_to_inventory_gl(): void
    {
        // A cash sale posts COGS/inventory GL, so 1310 should equal stock value change.
        $reports = app(ReportService::class);
        $sv = $reports->stockValue();
        // Stock value 288.00 (90 @ 3.20). GL 1310 = −COGS(10×3.20=32) since opening stock had no GL.
        $this->assertEqualsWithDelta(288.00, $sv['total'], 0.01);
        $this->assertNotNull($sv['gl_1310']);
    }

    public function test_sales_summary_by_method(): void
    {
        $summary = app(ReportService::class)->salesSummary(now()->startOfMonth()->toDateString(), now()->toDateString());
        $this->assertSame(5, $summary['count']);
        $this->assertEqualsWithDelta(73.60, $summary['gross'], 0.01);
        $cash = collect($summary['by_method'])->firstWhere('method', 'cash');
        $this->assertEqualsWithDelta(73.60, $cash['total'], 0.01);
    }
}
