<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\Part;
use App\Models\PriceList;
use App\Models\PriceListItem;
use App\Services\ArReceiptService;
use App\Services\GlPostingService;
use App\Services\SalesPostingService;
use App\Services\StockLedgerService;
use Database\Seeders\ChartOfAccountsSeeder;
use Database\Seeders\FinancialPeriodSeeder;
use Database\Seeders\NumberSequenceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Accounts Receivable (Module 7.3): receive against invoices, allocate,
 * clear the debtors control, and age the book.
 */
class ArReceiptFlowTest extends TestCase
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
        app(StockLedgerService::class)->post($this->part->id, $this->branch, 'OPENING_BALANCE', 50, 22.50, 'Test', 1);

        $retail = PriceList::create(['name' => 'Standard Retail', 'type' => 'retail', 'is_default' => true]);
        PriceListItem::create(['price_list_id' => $retail->id, 'part_id' => $this->part->id, 'price' => 45.00]);

        $this->customer = Customer::create([
            'customer_number' => 'TRADE-001', 'type' => 'business', 'name' => 'Highway Motors',
            'price_list_id' => $retail->id, 'payment_terms_days' => 30, 'credit_limit' => 5000,
        ]);
    }

    private function accountInvoice(float $qty): \App\Models\SalesDocument
    {
        $maths = app(\App\Services\PricingService::class)->computeLine($qty, 45.00, 0);

        return app(SalesPostingService::class)->postInvoice(
            $this->customer, $this->branch->id,
            [['part_id' => $this->part->id, 'qty' => $qty, 'unit_price' => 45.00]],
            [['method' => 'account', 'amount' => $maths['line_total_incl']]],
        );
    }

    public function test_receipt_allocation_clears_invoice_and_debtors(): void
    {
        $ar = app(ArReceiptService::class);
        $gl = app(GlPostingService::class);

        $inv = $this->accountInvoice(2); // 2 @ 45 = 90 excl → 103.50 incl on account
        $this->assertEqualsWithDelta(103.50, $this->customer->fresh()->arBalance(), 0.001);

        $open = $ar->openInvoices($this->customer);
        $this->assertCount(1, $open);
        $this->assertEqualsWithDelta(103.50, $open[0]['outstanding'], 0.001);

        $receipt = $ar->postReceipt(
            $this->customer, $this->branch->id, 'eft', 103.50,
            [['document_id' => $inv->id, 'amount' => 103.50]], 'EFT-001'
        );

        // Debtors cleared, bank up, customer flat.
        $this->assertEqualsWithDelta(0, $this->customer->fresh()->arBalance(), 0.001);
        $this->assertEqualsWithDelta(0, $gl->accountBalance('1210'), 0.001);
        $this->assertEqualsWithDelta(103.50, $gl->accountBalance('1120'), 0.001);
        $this->assertCount(0, $ar->openInvoices($this->customer));
        $this->assertEqualsWithDelta(0, $receipt->unallocated(), 0.001);
    }

    public function test_partial_receipt_leaves_outstanding(): void
    {
        $ar = app(ArReceiptService::class);
        $inv = $this->accountInvoice(2); // 103.50

        $ar->postReceipt($this->customer, $this->branch->id, 'cash', 50.00,
            [['document_id' => $inv->id, 'amount' => 50.00]]);

        $open = $ar->openInvoices($this->customer);
        $this->assertEqualsWithDelta(53.50, $open[0]['outstanding'], 0.001);
        $this->assertEqualsWithDelta(53.50, $this->customer->fresh()->arBalance(), 0.001);
    }

    public function test_over_allocation_is_rejected(): void
    {
        $ar = app(ArReceiptService::class);
        $inv = $this->accountInvoice(1); // 51.75

        $this->expectException(\InvalidArgumentException::class);
        $ar->postReceipt($this->customer, $this->branch->id, 'eft', 100.00,
            [['document_id' => $inv->id, 'amount' => 100.00]]);
    }

    public function test_unallocated_receipt_is_credit_on_account(): void
    {
        $ar = app(ArReceiptService::class);
        $this->accountInvoice(1); // 51.75 owed

        // Receive 60 but allocate only 51.75 → 8.25 sits as credit.
        $inv = $ar->openInvoices($this->customer)[0];
        $receipt = $ar->postReceipt($this->customer, $this->branch->id, 'eft', 60.00,
            [['document_id' => $inv['id'], 'amount' => 51.75]]);

        $this->assertEqualsWithDelta(8.25, $receipt->unallocated(), 0.001);
        // Net customer balance = 51.75 charged − 60 received = −8.25 (in credit).
        $this->assertEqualsWithDelta(-8.25, $this->customer->fresh()->arBalance(), 0.001);
    }

    public function test_ageing_buckets_current_invoice(): void
    {
        $ar = app(ArReceiptService::class);
        $this->accountInvoice(2);

        $ageing = $ar->ageing(now()->toDateString());
        $row = collect($ageing['rows'])->firstWhere('customer_id', $this->customer->id);
        $this->assertNotNull($row);
        $this->assertEqualsWithDelta(103.50, $row['current'], 0.001);
        $this->assertEqualsWithDelta(103.50, $row['total'], 0.001);
    }
}
