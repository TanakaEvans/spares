<?php

namespace Tests\Feature;

use App\Exceptions\UnpricedPartException;
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
 * THE go-live flow: scan → cart → tender cash → posted tax invoice.
 * Asserts stock, AVCO cost capture, balanced GL and change calculation.
 */
class CashSaleFlowTest extends TestCase
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
        $this->part = Part::factory()->create(['part_number' => 'Z762', 'description' => 'Oil Filter GUD']);

        // Opening stock: 36 @ 3.20 (AVCO 3.20).
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

    public function test_cash_sale_posts_stock_gl_and_change(): void
    {
        $sales = app(SalesPostingService::class);
        $gl = app(GlPostingService::class);

        // Sell 2 @ 6.40 excl → line 12.80 excl, VAT 1.92, total 14.72. Tender 20.
        $invoice = $sales->postInvoice(
            $this->walkIn,
            $this->branch->id,
            [['part_id' => $this->part->id, 'qty' => 2, 'unit_price' => 6.40, 'discount_pct' => 0]],
            [['method' => 'cash', 'amount' => 14.72, 'tendered' => 20.00]],
        );

        $this->assertSame('posted', $invoice->status);
        $this->assertEqualsWithDelta(12.80, (float) $invoice->subtotal_excl, 0.001);
        $this->assertEqualsWithDelta(1.92, (float) $invoice->vat_amount, 0.001);
        $this->assertEqualsWithDelta(14.72, (float) $invoice->total_incl, 0.001);

        // Stock down 36 → 34, AVCO unchanged.
        $level = StockLevel::where('part_id', $this->part->id)->where('branch_id', $this->branch->id)->first();
        $this->assertEqualsWithDelta(34, (float) $level->qty_on_hand, 0.001);

        // COGS captured at AVCO 3.20.
        $line = $invoice->lines->first();
        $this->assertEqualsWithDelta(3.20, (float) $line->unit_cost, 0.001);

        // Change given = 20 − 14.72.
        $payment = $invoice->payments->first();
        $this->assertEqualsWithDelta(5.28, (float) $payment->change_given, 0.001);

        // GL: Till 14.72 DR; Sales 12.80 CR + VAT 1.92 CR; COGS 6.40 DR / Inventory 6.40 CR.
        $this->assertEqualsWithDelta(14.72, $gl->accountBalance('1110'), 0.001);
        $this->assertEqualsWithDelta(12.80, $gl->accountBalance('4100'), 0.001);
        $this->assertEqualsWithDelta(1.92, $gl->accountBalance('2210'), 0.001);
        $this->assertEqualsWithDelta(6.40, $gl->accountBalance('5100'), 0.001);
    }

    public function test_card_sale_hits_bank_not_till(): void
    {
        $sales = app(SalesPostingService::class);
        $gl = app(GlPostingService::class);

        $sales->postInvoice(
            $this->walkIn,
            $this->branch->id,
            [['part_id' => $this->part->id, 'qty' => 1, 'unit_price' => 6.40]],
            [['method' => 'card', 'amount' => 7.36, 'reference' => 'AUTH123']],
        );

        $this->assertEqualsWithDelta(7.36, $gl->accountBalance('1120'), 0.001);
        $this->assertEqualsWithDelta(0, $gl->accountBalance('1110'), 0.001);
    }

    public function test_split_tender_cash_plus_card(): void
    {
        $sales = app(SalesPostingService::class);
        $gl = app(GlPostingService::class);

        // 3 @ 6.40 = 19.20 excl, VAT 2.88, total 22.08. Pay 10 cash + 12.08 card.
        $sales->postInvoice(
            $this->walkIn,
            $this->branch->id,
            [['part_id' => $this->part->id, 'qty' => 3, 'unit_price' => 6.40]],
            [
                ['method' => 'cash', 'amount' => 10.00, 'tendered' => 10.00],
                ['method' => 'card', 'amount' => 12.08, 'reference' => 'AUTH9'],
            ],
        );

        $this->assertEqualsWithDelta(10.00, $gl->accountBalance('1110'), 0.001);
        $this->assertEqualsWithDelta(12.08, $gl->accountBalance('1120'), 0.001);
    }

    public function test_payments_must_equal_total(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        app(SalesPostingService::class)->postInvoice(
            $this->walkIn,
            $this->branch->id,
            [['part_id' => $this->part->id, 'qty' => 1, 'unit_price' => 6.40]],
            [['method' => 'cash', 'amount' => 5.00, 'tendered' => 5.00]], // short
        );
    }

    public function test_unpriced_part_blocks_pricing(): void
    {
        $bare = Part::factory()->create(['part_number' => 'NOPRICE-1']);

        $this->expectException(UnpricedPartException::class);
        app(\App\Services\PricingService::class)->priceFor($bare, $this->walkIn);
    }
}
