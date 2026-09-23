<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\GlAccount;
use App\Models\Part;
use App\Models\PriceList;
use App\Models\PriceListItem;
use App\Services\LedgerReportService;
use App\Services\SalesPostingService;
use App\Services\StockLedgerService;
use Database\Seeders\ChartOfAccountsSeeder;
use Database\Seeders\FinancialPeriodSeeder;
use Database\Seeders\NumberSequenceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Financial reporting (Module 7.7): the ledger is balanced by construction, so
 * the trial balance and balance sheet must always balance, and the P&L must
 * tie to revenue − COGS.
 */
class FinancialReportsTest extends TestCase
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
        $this->part = Part::factory()->create(['part_number' => 'Z762']);
        app(StockLedgerService::class)->post($this->part->id, $this->branch, 'OPENING_BALANCE', 100, 3.20, 'Test', 1);

        $retail = PriceList::create(['name' => 'Standard Retail', 'type' => 'retail', 'is_default' => true]);
        PriceListItem::create(['price_list_id' => $retail->id, 'part_id' => $this->part->id, 'price' => 6.40]);

        $this->walkIn = Customer::create([
            'customer_number' => 'WALK-IN', 'type' => 'cash', 'name' => 'Cash Customer',
            'is_walk_in' => true, 'price_list_id' => $retail->id, 'credit_limit' => 0,
        ]);

        // Ten cash sales of 1 unit each.
        for ($i = 0; $i < 10; $i++) {
            app(SalesPostingService::class)->postInvoice(
                $this->walkIn, $this->branch->id,
                [['part_id' => $this->part->id, 'qty' => 1, 'unit_price' => 6.40]],
                [['method' => 'cash', 'amount' => 7.36, 'tendered' => 7.36]],
            );
        }
    }

    public function test_trial_balance_is_balanced(): void
    {
        $tb = app(LedgerReportService::class)->trialBalance(now()->toDateString());
        $this->assertTrue($tb['balanced']);
        $this->assertEqualsWithDelta($tb['total_debit'], $tb['total_credit'], 0.01);
        // Till: 10 × 7.36 = 73.60.
        $till = collect($tb['rows'])->firstWhere('account_code', '1110');
        $this->assertEqualsWithDelta(73.60, $till['debit'], 0.01);
    }

    public function test_income_statement_nets_revenue_less_cogs(): void
    {
        $pl = app(LedgerReportService::class)->incomeStatement('2000-01-01', now()->toDateString());
        // Revenue 10 × 6.40 = 64.00; COGS 10 × 3.20 = 32.00; profit 32.00.
        $this->assertEqualsWithDelta(64.00, $pl['total_revenue'], 0.01);
        $this->assertEqualsWithDelta(32.00, $pl['total_expense'], 0.01);
        $this->assertEqualsWithDelta(32.00, $pl['net_profit'], 0.01);
    }

    public function test_balance_sheet_balances(): void
    {
        $bs = app(LedgerReportService::class)->balanceSheet(now()->toDateString());
        $this->assertTrue($bs['balanced']);
        $this->assertEqualsWithDelta($bs['total_assets'], $bs['total_liabilities'] + $bs['total_equity'], 0.01);
        // Retained (current-year P&L) rolled into equity.
        $this->assertEqualsWithDelta(32.00, $bs['retained_current_year'], 0.01);
    }

    public function test_balance_sheet_balances_with_contra_accounts(): void
    {
        // A credit note creates Sales Returns (4900, contra-revenue) and, with a
        // supplier-side VAT input elsewhere, exercises accounts whose balance sits
        // on the opposite side of their section — the case that must still balance.
        $invoice = \App\Models\SalesDocument::where('document_type', 'invoice')->first();
        app(SalesPostingService::class)->postCreditNote(
            $invoice,
            [['line_id' => $invoice->lines->first()->id, 'qty' => 1, 'restock' => true]],
            'refund_cash',
            'Returned',
        );

        $bs = app(LedgerReportService::class)->balanceSheet(now()->toDateString());
        $this->assertTrue($bs['balanced'], 'Balance sheet must balance even with contra accounts.');
        $this->assertEqualsWithDelta($bs['total_assets'], $bs['total_liabilities'] + $bs['total_equity'], 0.01);

        $pl = app(LedgerReportService::class)->incomeStatement('2000-01-01', now()->toDateString());
        // Revenue 64.00 − returns 6.40 = 57.60 net; COGS 32.00 − 3.20 reversal = 28.80.
        $this->assertEqualsWithDelta(57.60, $pl['total_revenue'], 0.01);
        $this->assertEqualsWithDelta(28.80, $pl['total_expense'], 0.01);
    }

    public function test_account_movements_running_balance(): void
    {
        $till = GlAccount::where('account_code', '1110')->first();
        $mv = app(LedgerReportService::class)->accountMovements($till, '2000-01-01', now()->toDateString());
        $this->assertCount(10, $mv['rows']);
        $this->assertEqualsWithDelta(73.60, $mv['closing'], 0.01);
        $this->assertEqualsWithDelta(7.36, $mv['rows'][0]['balance'], 0.01);
    }
}
