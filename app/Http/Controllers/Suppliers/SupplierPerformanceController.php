<?php

namespace App\Http\Controllers\Suppliers;

use App\Http\Controllers\Controller;
use App\Models\GoodsReceivedNote;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\SupplierInvoice;
use Inertia\Inertia;
use Inertia\Response;

class SupplierPerformanceController extends Controller
{
    public function index(): Response
    {
        $rows = Supplier::orderBy('name')->get()->map(function (Supplier $s) {
            $pos = PurchaseOrder::where('supplier_id', $s->id)->get(['id', 'order_date', 'expected_date', 'status']);
            $grns = GoodsReceivedNote::where('supplier_id', $s->id)->with('po:id,expected_date,order_date')->get();

            $onTime = 0;
            $leadDays = [];
            foreach ($grns as $grn) {
                $expected = $grn->po?->expected_date;
                if ($expected && $grn->received_date && $grn->received_date->lte($expected)) {
                    $onTime++;
                }
                if ($grn->po?->order_date && $grn->received_date) {
                    $leadDays[] = $grn->po->order_date->diffInDays($grn->received_date);
                }
            }

            $spend = (float) SupplierInvoice::where('supplier_id', $s->id)->where('status', 'posted')->sum('total');

            return [
                'supplier_id' => $s->id,
                'supplier' => $s->name,
                'po_count' => $pos->count(),
                'grn_count' => $grns->count(),
                'spend' => round($spend, 2),
                'on_time_pct' => $grns->count() > 0 ? round($onTime / $grns->count() * 100, 0) : null,
                'avg_lead_days' => $leadDays ? round(array_sum($leadDays) / count($leadDays), 1) : null,
            ];
        })->filter(fn ($r) => $r['po_count'] > 0 || $r['spend'] > 0)->values();

        return Inertia::render('Suppliers/Performance/Index', ['rows' => $rows]);
    }
}
