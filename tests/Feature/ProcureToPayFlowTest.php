<?php

namespace Tests\Feature;

use App\Exceptions\OverReceiptException;
use App\Models\ApprovedSupplier;
use App\Models\Branch;
use App\Models\Part;
use App\Models\PurchaseOrder;
use App\Models\StockLevel;
use App\Models\Supplier;
use App\Services\GlPostingService;
use App\Services\GrnPostingService;
use App\Services\ReorderService;
use App\Services\StockLedgerService;
use App\Services\StockMovementService;
use App\Services\SupplierInvoiceService;
use Database\Seeders\ChartOfAccountsSeeder;
use Database\Seeders\FinancialPeriodSeeder;
use Database\Seeders\NumberSequenceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProcureToPayFlowTest extends TestCase
{
    use RefreshDatabase;

    private Branch $branch;

    private Supplier $supplier;

    private Part $part;

    protected function setUp(): void
    {
        parent::setUp();

        (new ChartOfAccountsSeeder)->run();
        (new FinancialPeriodSeeder)->run();
        (new NumberSequenceSeeder)->run();

        $this->branch = Branch::factory()->create();
        $this->supplier = Supplier::create([
            'supplier_number' => 'SUPP-00001',
            'name' => 'GUD Distributors',
            'payment_terms_days' => 30,
        ]);
        $this->part = Part::factory()->create(['part_number' => 'Z762']);
    }

    private function makePo(float $qty = 20, float $cost = 3.40): PurchaseOrder
    {
        $po = PurchaseOrder::create([
            'po_number' => 'PO-TEST-'.uniqid(),
            'supplier_id' => $this->supplier->id,
            'branch_id' => $this->branch->id,
            'status' => 'submitted',
            'order_date' => now()->toDateString(),
            'subtotal' => $qty * $cost,
            'total' => $qty * $cost,
        ]);

        $po->lines()->create([
            'part_id' => $this->part->id,
            'description' => 'Oil Filter',
            'qty_ordered' => $qty,
            'unit_cost' => $cost,
            'line_total' => round($qty * $cost, 2),
        ]);

        return $po;
    }

    // ── THE golden flow: PO → GRN → AVCO → 3-way match → AP ─────────────

    public function test_full_procure_to_pay_flow(): void
    {
        $gl = app(GlPostingService::class);

        // Pre-existing stock: 10 @ 5.00 (AVCO 5.00)
        app(StockLedgerService::class)->post($this->part->id, $this->branch, 'OPENING_BALANCE', 10, 5.00);

        $po = $this->makePo(20, 3.40);

        // ① Receive the full PO
        $grn = app(GrnPostingService::class)->receive($po, [
            ['po_line_id' => $po->lines->first()->id, 'qty_received' => 20],
        ]);

        // Stock: 30 on hand; AVCO = (10×5 + 20×3.40)/30 = 3.9333
        $level = StockLevel::where('part_id', $this->part->id)->where('branch_id', $this->branch->id)->first();
        $this->assertSame(30.0, (float) $level->qty_on_hand);
        $this->assertEqualsWithDelta(3.9333, (float) $level->average_cost, 0.0001);

        // PO fully received
        $this->assertSame('received', $po->fresh()->status);
        $this->assertSame(20.0, (float) $po->lines()->first()->qty_received);

        // GL accrual: Inventory ↑ 68.00, GRN Accruals ↑ 68.00
        // (the opening balance touched stock only — no GL in this test)
        $this->assertSame(68.0, $gl->accountBalance('1310'));
        $this->assertSame(68.0, $gl->accountBalance('2120'));
    }

    public function test_grn_posts_correct_gl_accrual(): void
    {
        $gl = app(GlPostingService::class);
        $po = $this->makePo(20, 3.40);

        app(GrnPostingService::class)->receive($po, [
            ['po_line_id' => $po->lines->first()->id, 'qty_received' => 20],
        ]);

        $this->assertSame(68.0, $gl->accountBalance('1310'));  // DR Inventory
        $this->assertSame(68.0, $gl->accountBalance('2120'));  // CR GRN Accruals
    }

    public function test_matched_invoice_posts_ap_and_clears_accrual(): void
    {
        $gl = app(GlPostingService::class);
        $po = $this->makePo(20, 3.40);
        $grn = app(GrnPostingService::class)->receive($po, [
            ['po_line_id' => $po->lines->first()->id, 'qty_received' => 20],
        ]);

        $invoice = app(SupplierInvoiceService::class)->capture(
            $grn, 'GUD-INV-991', now()->toDateString(), 68.00, 10.20
        );

        $this->assertSame('posted', $invoice->status);
        $this->assertSame(0.0, $gl->accountBalance('2120'));   // accrual cleared
        $this->assertSame(10.2, $gl->accountBalance('2220'));  // VAT input
        $this->assertSame(78.2, $gl->accountBalance('2110'));  // creditors
        $this->assertSame(78.2, $this->supplier->fresh()->apBalance());
        $this->assertSame(now()->addDays(30)->toDateString(), $invoice->due_date->toDateString());
    }

    public function test_invoice_outside_tolerance_is_disputed_not_posted(): void
    {
        $po = $this->makePo(20, 3.40); // GRN value 68.00
        $grn = app(GrnPostingService::class)->receive($po, [
            ['po_line_id' => $po->lines->first()->id, 'qty_received' => 20],
        ]);

        // 95.00 vs 68.00 — way beyond max(2%, $10)
        $invoice = app(SupplierInvoiceService::class)->capture(
            $grn, 'GUD-INV-992', now()->toDateString(), 95.00, 14.25
        );

        $this->assertSame('disputed', $invoice->status);
        $this->assertNotNull($invoice->dispute_reason);
        $this->assertSame(0.0, app(GlPostingService::class)->accountBalance('2110'));
    }

    public function test_partial_receipt_marks_po_partial_and_second_grn_completes(): void
    {
        $po = $this->makePo(20, 3.40);
        $svc = app(GrnPostingService::class);

        $svc->receive($po, [['po_line_id' => $po->lines->first()->id, 'qty_received' => 12]]);
        $this->assertSame('partial', $po->fresh()->status);

        $svc->receive($po->fresh(), [['po_line_id' => $po->lines->first()->id, 'qty_received' => 8]]);
        $this->assertSame('received', $po->fresh()->status);
    }

    public function test_over_receipt_beyond_tolerance_blocked_without_approval(): void
    {
        $po = $this->makePo(20, 3.40); // 10% tolerance → max 22

        $this->expectException(OverReceiptException::class);

        app(GrnPostingService::class)->receive($po, [
            ['po_line_id' => $po->lines->first()->id, 'qty_received' => 25],
        ]);
    }

    public function test_over_receipt_allowed_with_supervisor_approval(): void
    {
        $po = $this->makePo(20, 3.40);

        $grn = app(GrnPostingService::class)->receive($po, [
            ['po_line_id' => $po->lines->first()->id, 'qty_received' => 25],
        ], overReceiptApproved: true);

        $this->assertSame(25.0, (float) $grn->lines->first()->qty_received);
    }

    public function test_rejected_qty_never_enters_stock(): void
    {
        $po = $this->makePo(20, 3.40);

        app(GrnPostingService::class)->receive($po, [
            ['po_line_id' => $po->lines->first()->id, 'qty_received' => 18, 'qty_rejected' => 2, 'rejection_reason' => 'crushed'],
        ]);

        $level = StockLevel::where('part_id', $this->part->id)->first();
        $this->assertSame(18.0, (float) $level->qty_on_hand);
    }

    public function test_draft_po_cannot_be_received(): void
    {
        $po = $this->makePo();
        $po->update(['status' => 'draft']);

        $this->expectException(\InvalidArgumentException::class);

        app(GrnPostingService::class)->receive($po, [
            ['po_line_id' => $po->lines->first()->id, 'qty_received' => 5],
        ]);
    }

    // ── Returns ──────────────────────────────────────────────────────────

    public function test_supplier_return_flow_ships_stock_and_posts_credit(): void
    {
        $gl = app(GlPostingService::class);
        $moves = app(StockMovementService::class);

        app(StockLedgerService::class)->post($this->part->id, $this->branch, 'OPENING_BALANCE', 10, 4.00);

        $return = $moves->createSupplierReturn($this->supplier->id, $this->branch->id, 'damaged', [
            ['part_id' => $this->part->id, 'qty' => 3, 'condition' => 'damaged'],
        ]);

        // RMA gate
        try {
            $moves->shipSupplierReturn($return, '');
            $this->fail('Expected RMA requirement');
        } catch (\InvalidArgumentException) {
        }

        $moves->shipSupplierReturn($return, 'RMA-778');
        $this->assertSame(7.0, (float) StockLevel::where('part_id', $this->part->id)->first()->qty_on_hand);
        $this->assertSame('shipped', $return->fresh()->status);

        $moves->creditSupplierReturn($return->fresh(), 'GUD-CN-31', 12.00);
        $this->assertSame('credited', $return->fresh()->status);
        $this->assertSame(-12.0, $gl->accountBalance('2110'));  // creditors reduced
    }

    // ── Adjustments & transfers ──────────────────────────────────────────

    public function test_adjustment_out_posts_write_off_journal(): void
    {
        $gl = app(GlPostingService::class);
        app(StockLedgerService::class)->post($this->part->id, $this->branch, 'OPENING_BALANCE', 10, 4.00);

        app(StockMovementService::class)->postAdjustment($this->branch->id, 'damage', [
            ['part_id' => $this->part->id, 'direction' => 'out', 'qty' => 2],
        ]);

        $this->assertSame(8.0, (float) StockLevel::where('part_id', $this->part->id)->first()->qty_on_hand);
        $this->assertSame(8.0, $gl->accountBalance('5300'));   // 2 × 4.00 write-off
        $this->assertSame(-8.0, $gl->accountBalance('1310'));
    }

    public function test_transfer_moves_stock_between_branches_at_source_avco(): void
    {
        $other = Branch::factory()->create();
        $moves = app(StockMovementService::class);
        app(StockLedgerService::class)->post($this->part->id, $this->branch, 'OPENING_BALANCE', 10, 4.00);

        $transfer = $moves->createTransfer($this->branch->id, $other->id, [
            ['part_id' => $this->part->id, 'qty' => 4],
        ]);
        $moves->dispatchTransfer($transfer);

        $this->assertSame(6.0, (float) StockLevel::where('branch_id', $this->branch->id)->where('part_id', $this->part->id)->first()->qty_on_hand);
        $this->assertNull(StockLevel::where('branch_id', $other->id)->where('part_id', $this->part->id)->first()?->qty_on_hand);

        $moves->receiveTransfer($transfer->fresh());

        $dest = StockLevel::where('branch_id', $other->id)->where('part_id', $this->part->id)->first();
        $this->assertSame(4.0, (float) $dest->qty_on_hand);
        $this->assertSame(4.0, (float) $dest->average_cost); // arrived at source AVCO
    }

    // ── Reorder ──────────────────────────────────────────────────────────

    public function test_reorder_report_and_draft_po_creation(): void
    {
        app(StockLedgerService::class)->post($this->part->id, $this->branch, 'OPENING_BALANCE', 3, 4.00);
        StockLevel::where('part_id', $this->part->id)->update(['reorder_point' => 5, 'reorder_qty' => 24]);
        ApprovedSupplier::create(['part_id' => $this->part->id, 'supplier_id' => $this->supplier->id, 'is_preferred' => true]);

        $reorder = app(ReorderService::class);
        $rows = $reorder->belowReorder($this->branch->id);

        $this->assertCount(1, $rows);
        $this->assertSame('Z762', $rows[0]['part_number']);
        $this->assertSame(24.0, $rows[0]['suggested_qty']);
        $this->assertSame($this->supplier->id, $rows[0]['preferred_supplier_id']);

        $pos = $reorder->createDraftPos($this->branch->id, [
            ['part_id' => $this->part->id, 'qty' => 24, 'supplier_id' => $this->supplier->id],
        ]);

        $this->assertCount(1, $pos);
        $po = $pos->first();
        $this->assertSame('draft', $po->status);
        $this->assertSame(24.0, (float) $po->lines->first()->qty_ordered);
        $this->assertSame(4.0, (float) $po->lines->first()->unit_cost); // fell back to AVCO
    }
}
