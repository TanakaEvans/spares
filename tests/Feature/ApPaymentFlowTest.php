<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Supplier;
use App\Models\SupplierInvoice;
use App\Services\ApPaymentService;
use App\Services\GlPostingService;
use Database\Seeders\ChartOfAccountsSeeder;
use Database\Seeders\FinancialPeriodSeeder;
use Database\Seeders\NumberSequenceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Accounts Payable (Module 7.4): pay suppliers, allocate to invoices, run a
 * batch payment, clear the creditors control.
 */
class ApPaymentFlowTest extends TestCase
{
    use RefreshDatabase;

    private Branch $branch;

    private Supplier $supplierA;

    private Supplier $supplierB;

    protected function setUp(): void
    {
        parent::setUp();

        (new ChartOfAccountsSeeder)->run();
        (new FinancialPeriodSeeder)->run();
        (new NumberSequenceSeeder)->run();

        $this->branch = Branch::factory()->create();
        $this->supplierA = Supplier::create(['supplier_number' => 'SUPP-00001', 'name' => 'GUD Distributors', 'payment_terms_days' => 30]);
        $this->supplierB = Supplier::create(['supplier_number' => 'SUPP-00002', 'name' => 'Bosch SA', 'payment_terms_days' => 30]);
    }

    private function postedInvoice(Supplier $supplier, float $total): SupplierInvoice
    {
        $po = \App\Models\PurchaseOrder::create([
            'po_number' => 'PO-'.uniqid(),
            'supplier_id' => $supplier->id,
            'branch_id' => $this->branch->id,
            'status' => 'received',
            'order_date' => now()->toDateString(),
            'subtotal' => $total, 'total' => $total,
        ]);
        $grn = \App\Models\GoodsReceivedNote::create([
            'grn_number' => 'GRN-'.uniqid(),
            'po_id' => $po->id,
            'supplier_id' => $supplier->id,
            'branch_id' => $this->branch->id,
            'received_date' => now()->toDateString(),
            'status' => 'posted',
        ]);

        return SupplierInvoice::create([
            'invoice_number' => 'SI-'.uniqid(),
            'supplier_ref' => 'REF-'.random_int(1000, 9999),
            'supplier_id' => $supplier->id,
            'grn_id' => $grn->id,
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'subtotal' => round($total / 1.15, 2),
            'vat_amount' => round($total - $total / 1.15, 2),
            'total' => $total,
            'status' => 'posted',
        ]);
    }

    public function test_payment_allocation_clears_invoice_and_creditors(): void
    {
        $ap = app(ApPaymentService::class);
        $gl = app(GlPostingService::class);

        $inv = $this->postedInvoice($this->supplierA, 230.00);
        $this->assertEqualsWithDelta(230.00, $this->supplierA->fresh()->apBalance(), 0.001);
        $this->assertEqualsWithDelta(230.00, $ap->openInvoices($this->supplierA)[0]['outstanding'], 0.001);

        $payment = $ap->postPayment($this->supplierA, $this->branch->id, 'eft', 230.00,
            [['supplier_invoice_id' => $inv->id, 'amount' => 230.00]], 'EFT-777');

        $this->assertEqualsWithDelta(0, $this->supplierA->fresh()->apBalance(), 0.001);
        // This isolated test posts no invoice-side credit, so the payment's DR to
        // the creditors control leaves it at −230 (it would net to 0 in a full flow).
        $this->assertEqualsWithDelta(-230.00, $gl->accountBalance('2110'), 0.001);
        $this->assertEqualsWithDelta(-230.00, $gl->accountBalance('1120'), 0.001); // bank down
        $this->assertCount(0, $ap->openInvoices($this->supplierA));
        $this->assertSame('EFT-777', $payment->reference);
    }

    public function test_over_allocation_is_rejected(): void
    {
        $ap = app(ApPaymentService::class);
        $inv = $this->postedInvoice($this->supplierA, 100.00);

        $this->expectException(\InvalidArgumentException::class);
        $ap->postPayment($this->supplierA, $this->branch->id, 'eft', 200.00,
            [['supplier_invoice_id' => $inv->id, 'amount' => 200.00]]);
    }

    public function test_payment_run_pays_two_suppliers_under_one_batch(): void
    {
        $ap = app(ApPaymentService::class);

        $a = $this->postedInvoice($this->supplierA, 100.00);
        $b = $this->postedInvoice($this->supplierB, 250.00);

        $result = $ap->runBatch([$a->id, $b->id], $this->branch->id, 'eft');

        $this->assertStringStartsWith('RUN-', $result['batch_ref']);
        $this->assertCount(2, $result['payments']);
        $this->assertEqualsWithDelta(0, $this->supplierA->fresh()->apBalance(), 0.001);
        $this->assertEqualsWithDelta(0, $this->supplierB->fresh()->apBalance(), 0.001);
        foreach ($result['payments'] as $p) {
            $this->assertSame($result['batch_ref'], $p->batch_ref);
        }
    }

    public function test_ageing_lists_open_invoice(): void
    {
        $ap = app(ApPaymentService::class);
        $this->postedInvoice($this->supplierA, 460.00);

        $ageing = $ap->ageing(now()->toDateString());
        $row = collect($ageing['rows'])->firstWhere('supplier_id', $this->supplierA->id);
        $this->assertEqualsWithDelta(460.00, $row['current'], 0.001);
    }
}
