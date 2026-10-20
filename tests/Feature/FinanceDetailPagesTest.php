<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\GoodsReceivedNote;
use App\Models\Part;
use App\Models\PriceList;
use App\Models\PriceListItem;
use App\Models\PurchaseOrder;
use App\Models\Role;
use App\Models\Supplier;
use App\Models\SupplierInvoice;
use App\Models\User;
use App\Services\ApPaymentService;
use App\Services\ArReceiptService;
use App\Services\PricingService;
use App\Services\SalesPostingService;
use App\Services\StockLedgerService;
use Database\Seeders\ChartOfAccountsSeeder;
use Database\Seeders\FinancialPeriodSeeder;
use Database\Seeders\NumberSequenceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * The receipt and payment detail pages (UI-13): every posted sub-ledger
 * document has a page of its own, with its allocations, journal and parties
 * exposed as link targets.
 */
class FinanceDetailPagesTest extends TestCase
{
    use RefreshDatabase;

    private Branch $branch;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        (new ChartOfAccountsSeeder)->run();
        (new FinancialPeriodSeeder)->run();
        (new NumberSequenceSeeder)->run();

        $this->branch = Branch::factory()->create();

        $role = Role::create(['name' => 'Accountant', 'description' => 'Accountant']);
        $this->user = User::factory()->create(['password_changed_at' => now()]);
        $this->user->roles()->attach($role->id, ['created_at' => now(), 'updated_at' => now()]);
    }

    public function test_receipt_show_page_renders_allocations_and_journal(): void
    {
        $part = Part::factory()->create(['part_number' => 'DB2074', 'description' => 'Brake Pads']);
        app(StockLedgerService::class)->post($part->id, $this->branch, 'OPENING_BALANCE', 50, 22.50, 'Test', 1);

        $retail = PriceList::create(['name' => 'Standard Retail', 'type' => 'retail', 'is_default' => true]);
        PriceListItem::create(['price_list_id' => $retail->id, 'part_id' => $part->id, 'price' => 45.00]);

        $customer = Customer::create([
            'customer_number' => 'TRADE-001', 'type' => 'business', 'name' => 'Highway Motors',
            'price_list_id' => $retail->id, 'payment_terms_days' => 30, 'credit_limit' => 5000,
        ]);

        $maths = app(PricingService::class)->computeLine(2, 45.00, 0);
        $invoice = app(SalesPostingService::class)->postInvoice(
            $customer, $this->branch->id,
            [['part_id' => $part->id, 'qty' => 2, 'unit_price' => 45.00]],
            [['method' => 'account', 'amount' => $maths['line_total_incl']]],
        );

        $receipt = app(ArReceiptService::class)->postReceipt(
            $customer, $this->branch->id, 'eft', 103.50,
            [['document_id' => $invoice->id, 'amount' => 103.50]], 'EFT-001', null, $this->user->id,
        );

        $this->actingAs($this->user)
            ->get(route('finance.receipts.show', $receipt))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Finance/Receipts/Show')
                ->where('receipt.id', $receipt->id)
                ->where('receipt.receipt_number', $receipt->receipt_number)
                ->where('receipt.customer.id', $customer->id)
                ->where('receipt.amount', fn ($v) => abs($v - 103.5) < 0.001)
                ->where('receipt.unallocated', fn ($v) => abs($v) < 0.001)
                ->where('receipt.journal.id', $receipt->gl_journal_id)
                ->where('receipt.received_by.id', $this->user->id)
                ->has('receipt.allocations', 1, fn (Assert $a) => $a
                    ->where('document_id', $invoice->id)
                    ->where('document_number', $invoice->document_number)
                    ->where('amount', fn ($v) => abs($v - 103.5) < 0.001)
                    ->etc())
            );
    }

    public function test_payment_show_page_renders_allocations_and_journal(): void
    {
        $supplier = Supplier::create(['supplier_number' => 'SUPP-00001', 'name' => 'GUD Distributors', 'payment_terms_days' => 30]);

        $po = PurchaseOrder::create([
            'po_number' => 'PO-'.uniqid(), 'supplier_id' => $supplier->id, 'branch_id' => $this->branch->id,
            'status' => 'received', 'order_date' => now()->toDateString(), 'subtotal' => 230, 'total' => 230,
        ]);
        $grn = GoodsReceivedNote::create([
            'grn_number' => 'GRN-'.uniqid(), 'po_id' => $po->id, 'supplier_id' => $supplier->id,
            'branch_id' => $this->branch->id, 'received_date' => now()->toDateString(), 'status' => 'posted',
        ]);
        $invoice = SupplierInvoice::create([
            'invoice_number' => 'SI-0001', 'supplier_ref' => 'REF-4471', 'supplier_id' => $supplier->id, 'grn_id' => $grn->id,
            'invoice_date' => now()->toDateString(), 'due_date' => now()->addDays(30)->toDateString(),
            'subtotal' => 200, 'vat_amount' => 30, 'total' => 230, 'status' => 'posted',
        ]);

        $payment = app(ApPaymentService::class)->postPayment(
            $supplier, $this->branch->id, 'eft', 230.00,
            [['supplier_invoice_id' => $invoice->id, 'amount' => 230.00]], 'EFT-777', null, $this->user->id,
        );

        $this->actingAs($this->user)
            ->get(route('finance.payments.show', $payment))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Finance/Payments/Show')
                ->where('payment.id', $payment->id)
                ->where('payment.payment_number', $payment->payment_number)
                ->where('payment.supplier.id', $supplier->id)
                ->where('payment.amount', fn ($v) => abs($v - 230) < 0.001)
                ->where('payment.unallocated', fn ($v) => abs($v) < 0.001)
                ->where('payment.journal.id', $payment->gl_journal_id)
                ->where('payment.paid_by.id', $this->user->id)
                ->has('payment.allocations', 1, fn (Assert $a) => $a
                    ->where('supplier_invoice_id', $invoice->id)
                    ->where('invoice_number', 'SI-0001')
                    ->where('amount', fn ($v) => abs($v - 230) < 0.001)
                    ->etc())
            );
    }

    public function test_journal_show_links_back_to_its_source_receipt(): void
    {
        $customer = Customer::create([
            'customer_number' => 'TRADE-002', 'type' => 'business', 'name' => 'Bochum Taxis',
            'payment_terms_days' => 30, 'credit_limit' => 5000,
        ]);
        $receipt = app(ArReceiptService::class)->postReceipt($customer, $this->branch->id, 'cash', 50.00, [], null, null, $this->user->id);

        $this->actingAs($this->user)
            ->get(route('finance.journals.show', $receipt->gl_journal_id))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Finance/Journals/Show')
                ->where('journal.source.type', 'receipt')
                ->where('journal.source.id', $receipt->id)
                ->where('journal.source.label', $receipt->receipt_number)
                ->where('journal.posted_by.id', $this->user->id)
            );
    }
}
