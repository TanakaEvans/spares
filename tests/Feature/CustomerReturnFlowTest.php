<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\Part;
use App\Models\PriceList;
use App\Models\PriceListItem;
use App\Models\StockLevel;
use App\Services\GlPostingService;
use App\Services\SalesPostingService;
use App\Services\StockLedgerService;
use Database\Seeders\ChartOfAccountsSeeder;
use Database\Seeders\FinancialPeriodSeeder;
use Database\Seeders\NumberSequenceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Credit notes against a posted invoice (Module 2.5): qty caps, restock at the
 * original cost, VAT reversed at the original rate, cash vs account refund.
 */
class CustomerReturnFlowTest extends TestCase
{
    use RefreshDatabase;

    private Branch $branch;

    private Part $part;

    private Customer $walkIn;

    protected function setUp(): void
    {
        parent::setUp();

        (new ChartOfAccountsSeeder)->run();
        (new FinancialPeriodSeeder)->run();
        (new NumberSequenceSeeder)->run();

        $this->branch = Branch::factory()->create();
        $this->part = Part::factory()->create(['part_number' => 'Z762', 'description' => 'Oil Filter']);

        app(StockLedgerService::class)->post(
            $this->part->id, $this->branch, 'OPENING_BALANCE', 36, 3.20, 'Test', 1
        );

        $retail = PriceList::create(['name' => 'Standard Retail', 'type' => 'retail', 'is_default' => true]);
        PriceListItem::create(['price_list_id' => $retail->id, 'part_id' => $this->part->id, 'price' => 6.40]);

        $this->walkIn = Customer::create([
            'customer_number' => 'WALK-IN', 'type' => 'cash', 'name' => 'Cash Customer',
            'is_walk_in' => true, 'price_list_id' => $retail->id, 'credit_limit' => 0,
        ]);
    }

    private function sellCash(float $qty): \App\Models\SalesDocument
    {
        $maths = app(\App\Services\PricingService::class)->computeLine($qty, 6.40, 0);

        return app(SalesPostingService::class)->postInvoice(
            $this->walkIn,
            $this->branch->id,
            [['part_id' => $this->part->id, 'qty' => $qty, 'unit_price' => 6.40]],
            [['method' => 'cash', 'amount' => $maths['line_total_incl'], 'tendered' => $maths['line_total_incl']]],
        );
    }

    public function test_partial_cash_refund_restocks_and_reverses_gl(): void
    {
        $gl = app(GlPostingService::class);
        $invoice = $this->sellCash(4); // stock 36 → 32

        $line = $invoice->lines->first();

        // Return 1 of 4, cash refund, restock.
        $credit = app(SalesPostingService::class)->postCreditNote(
            $invoice,
            [['line_id' => $line->id, 'qty' => 1, 'restock' => true]],
            'refund_cash',
            'Wrong part',
        );

        $this->assertSame('credit_note', $credit->document_type);
        $this->assertEqualsWithDelta(6.40, (float) $credit->subtotal_excl, 0.001);
        $this->assertEqualsWithDelta(0.96, (float) $credit->vat_amount, 0.001);
        $this->assertEqualsWithDelta(7.36, (float) $credit->total_incl, 0.001);

        // Stock back to 33.
        $level = StockLevel::where('part_id', $this->part->id)->where('branch_id', $this->branch->id)->first();
        $this->assertEqualsWithDelta(33, (float) $level->qty_on_hand, 0.001);

        // qty_credited on the invoice line now 1.
        $this->assertEqualsWithDelta(1, (float) $line->fresh()->qty_credited, 0.001);

        // GL: Sales Returns 6.40 DR, VAT 0.96 DR, Till 7.36 CR; Inventory 3.20 DR / COGS 3.20 CR.
        $this->assertEqualsWithDelta(6.40, $gl->accountBalance('4900'), 0.001);
    }

    public function test_cannot_credit_more_than_sold(): void
    {
        $invoice = $this->sellCash(2);
        $line = $invoice->lines->first();

        $this->expectException(\InvalidArgumentException::class);
        app(SalesPostingService::class)->postCreditNote(
            $invoice,
            [['line_id' => $line->id, 'qty' => 3, 'restock' => true]],
            'refund_cash',
            'Too many',
        );
    }

    public function test_credit_without_restock_leaves_stock(): void
    {
        $invoice = $this->sellCash(2); // 36 → 34
        $line = $invoice->lines->first();

        app(SalesPostingService::class)->postCreditNote(
            $invoice,
            [['line_id' => $line->id, 'qty' => 1, 'restock' => false]], // faulty, scrapped
            'refund_cash',
            'Faulty — scrapped',
        );

        $level = StockLevel::where('part_id', $this->part->id)->where('branch_id', $this->branch->id)->first();
        $this->assertEqualsWithDelta(34, (float) $level->qty_on_hand, 0.001);
    }
}
