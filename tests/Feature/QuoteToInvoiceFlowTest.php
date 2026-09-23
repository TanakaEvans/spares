<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\Part;
use App\Models\PriceList;
use App\Models\PriceListItem;
use App\Models\StockLevel;
use App\Services\SalesPostingService;
use App\Services\SalesQuoteService;
use App\Services\StockLedgerService;
use Database\Seeders\ChartOfAccountsSeeder;
use Database\Seeders\FinancialPeriodSeeder;
use Database\Seeders\NumberSequenceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Quote → order → invoice (Modules 2.2/2.3): expiry gate, stock reservation on
 * confirm, reservation release on fulfilment, order marked invoiced.
 */
class QuoteToInvoiceFlowTest extends TestCase
{
    use RefreshDatabase;

    private Branch $branch;

    private Part $part;

    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        (new ChartOfAccountsSeeder)->run();
        (new FinancialPeriodSeeder)->run();
        (new NumberSequenceSeeder)->run();

        $this->branch = Branch::factory()->create();
        $this->part = Part::factory()->create(['part_number' => 'DB2074', 'description' => 'Brake Pads']);

        app(StockLedgerService::class)->post(
            $this->part->id, $this->branch, 'OPENING_BALANCE', 20, 22.50, 'Test', 1
        );

        $retail = PriceList::create(['name' => 'Standard Retail', 'type' => 'retail', 'is_default' => true]);
        PriceListItem::create(['price_list_id' => $retail->id, 'part_id' => $this->part->id, 'price' => 45.00]);

        $this->customer = Customer::create([
            'customer_number' => 'TRADE-001', 'type' => 'business', 'name' => 'Highway Motors',
            'price_list_id' => $retail->id, 'payment_terms_days' => 30, 'credit_limit' => 1000,
        ]);
    }

    public function test_quote_prices_from_list(): void
    {
        $quote = app(SalesQuoteService::class)->createQuote(
            $this->customer,
            $this->branch->id,
            [['part_id' => $this->part->id, 'qty' => 2]], // no unit_price → resolved
        );

        $this->assertSame('quotation', $quote->document_type);
        $this->assertSame('open', $quote->status);
        $this->assertEqualsWithDelta(45.00, (float) $quote->lines->first()->unit_price, 0.001);
        $this->assertEqualsWithDelta(90.00, (float) $quote->subtotal_excl, 0.001);
        $this->assertNotNull($quote->expiry_date);
    }

    public function test_convert_reserves_stock_and_invoice_releases_it(): void
    {
        $quotes = app(SalesQuoteService::class);
        $sales = app(SalesPostingService::class);

        $quote = $quotes->createQuote(
            $this->customer, $this->branch->id,
            [['part_id' => $this->part->id, 'qty' => 5, 'unit_price' => 45.00]],
        );

        $order = $quotes->convertToOrder($quote);
        $this->assertSame('order', $order->document_type);
        $this->assertSame('confirmed', $order->status);
        $this->assertSame('converted', $quote->fresh()->status);

        // 5 reserved; qty_on_hand still 20 but availability 15.
        $level = StockLevel::where('part_id', $this->part->id)->where('branch_id', $this->branch->id)->first();
        $this->assertEqualsWithDelta(5, (float) $level->qty_reserved, 0.001);
        $this->assertEqualsWithDelta(20, (float) $level->qty_on_hand, 0.001);

        // Fulfil → invoice releases the reservation and moves the stock.
        $invoice = $sales->postInvoice(
            $this->customer, $this->branch->id,
            [['part_id' => $this->part->id, 'qty' => 5, 'unit_price' => 45.00]],
            [['method' => 'account', 'amount' => 258.75]],
            null,
            $order,
        );

        $this->assertSame('invoiced', $order->fresh()->status);
        $level->refresh();
        $this->assertEqualsWithDelta(0, (float) $level->qty_reserved, 0.001);
        $this->assertEqualsWithDelta(15, (float) $level->qty_on_hand, 0.001);
        $this->assertSame($order->id, $invoice->parent_id);
    }

    public function test_expired_quote_cannot_convert(): void
    {
        $quote = app(SalesQuoteService::class)->createQuote(
            $this->customer, $this->branch->id,
            [['part_id' => $this->part->id, 'qty' => 1, 'unit_price' => 45.00]],
        );
        $quote->update(['expiry_date' => now()->subDay()->toDateString()]);

        $this->expectException(\InvalidArgumentException::class);
        app(SalesQuoteService::class)->convertToOrder($quote->fresh());
    }

    public function test_cancel_order_releases_reservation(): void
    {
        $quotes = app(SalesQuoteService::class);

        $quote = $quotes->createQuote(
            $this->customer, $this->branch->id,
            [['part_id' => $this->part->id, 'qty' => 4, 'unit_price' => 45.00]],
        );
        $order = $quotes->convertToOrder($quote);

        $quotes->cancelOrder($order);

        $this->assertSame('cancelled', $order->fresh()->status);
        $level = StockLevel::where('part_id', $this->part->id)->where('branch_id', $this->branch->id)->first();
        $this->assertEqualsWithDelta(0, (float) $level->qty_reserved, 0.001);
    }

    public function test_reprice_check_flags_changed_prices(): void
    {
        $quotes = app(SalesQuoteService::class);

        $quote = $quotes->createQuote(
            $this->customer, $this->branch->id,
            [['part_id' => $this->part->id, 'qty' => 1, 'unit_price' => 40.00]], // quoted below list
        );

        $changed = $quotes->repriceCheck($quote);
        $this->assertCount(1, $changed);
        $this->assertEqualsWithDelta(40.00, $changed[0]['was'], 0.001);
        $this->assertEqualsWithDelta(45.00, $changed[0]['now'], 0.001);
    }
}
