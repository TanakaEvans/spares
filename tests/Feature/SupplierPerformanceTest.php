<?php

namespace Tests\Feature;

use App\Http\Controllers\Suppliers\SupplierPerformanceController;
use App\Models\Branch;
use App\Models\GoodsReceivedNote;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

/**
 * Supplier performance report: on-time delivery and lead time derive from each
 * GRN's purchase order. Regression guard for the relationship + date maths.
 */
class SupplierPerformanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_report_computes_on_time_and_lead_time_from_grn_purchase_order(): void
    {
        $branch = Branch::factory()->create();
        $supplier = Supplier::create(['supplier_number' => 'SUPP-1', 'name' => 'Test Supplier']);

        $po = PurchaseOrder::create([
            'po_number' => 'PO-1', 'supplier_id' => $supplier->id, 'branch_id' => $branch->id,
            'order_date' => '2026-01-01', 'expected_date' => '2026-01-10', 'status' => 'submitted',
        ]);
        GoodsReceivedNote::create([
            'grn_number' => 'GRN-1', 'po_id' => $po->id, 'supplier_id' => $supplier->id,
            'branch_id' => $branch->id, 'received_date' => '2026-01-08', 'status' => 'posted',
        ]);

        $request = Request::create('/suppliers/lists/performance', 'GET');
        $request->headers->set('X-Inertia', 'true');
        $page = json_decode(
            app(SupplierPerformanceController::class)->index()->toResponse($request)->getContent(),
            true
        );

        $rows = $page['props']['rows'];
        $this->assertCount(1, $rows);
        $this->assertSame('Test Supplier', $rows[0]['supplier']);
        $this->assertSame(1, $rows[0]['grn_count']);
        $this->assertEqualsWithDelta(100, $rows[0]['on_time_pct'], 0.001); // received 08 Jan, before 10 Jan
        $this->assertEqualsWithDelta(7, $rows[0]['avg_lead_days'], 0.001);  // 01 → 08 Jan
    }
}
