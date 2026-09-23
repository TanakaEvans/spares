<?php

namespace App\Http\Controllers\Purchasing;

use App\Http\Controllers\Controller;
use App\Models\BinLocation;
use App\Models\GoodsReceivedNote;
use App\Models\PurchaseOrder;
use App\Services\GrnPostingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class GrnController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('Purchasing/GRNs/Index', [
            'grns' => GoodsReceivedNote::with('supplier:id,name', 'purchaseOrder:id,po_number', 'branch:id,name')
                ->latest('received_date')->latest('id')
                ->paginate(25)
                ->withQueryString()
                ->through(fn (GoodsReceivedNote $g) => [
                    'id' => $g->id,
                    'grn_number' => $g->grn_number,
                    'po_number' => $g->purchaseOrder?->po_number,
                    'po_id' => $g->po_id,
                    'supplier' => $g->supplier?->name,
                    'branch' => $g->branch?->name,
                    'received_date' => $g->received_date->toDateString(),
                    'status' => $g->status,
                    'value' => $g->acceptedValue(),
                ]),
            'awaiting' => PurchaseOrder::with('supplier:id,name')
                ->whereIn('status', ['submitted', 'confirmed', 'partial'])
                ->latest('order_date')->limit(10)->get()
                ->map(fn ($po) => [
                    'id' => $po->id, 'po_number' => $po->po_number,
                    'supplier' => $po->supplier?->name, 'status' => $po->status,
                ]),
        ]);
    }

    public function create(PurchaseOrder $order): Response
    {
        abort_unless($order->isReceivable(), 422, 'This PO cannot be received against.');

        $order->load('supplier:id,name', 'lines.part:id,part_number');

        return Inertia::render('Purchasing/GRNs/Create', [
            'order' => [
                'id' => $order->id,
                'po_number' => $order->po_number,
                'supplier' => $order->supplier?->name,
                'branch_id' => $order->branch_id,
                'lines' => $order->lines
                    ->filter(fn ($l) => (float) $l->qty_received < (float) $l->qty_ordered * 1.5)
                    ->values()
                    ->map(fn ($l) => [
                        'po_line_id' => $l->id,
                        'part_number' => $l->part?->part_number,
                        'description' => $l->description,
                        'qty_ordered' => (float) $l->qty_ordered,
                        'qty_already_received' => (float) $l->qty_received,
                        'qty_outstanding' => max(0, (float) $l->qty_ordered - (float) $l->qty_received),
                        'unit_cost' => (float) $l->unit_cost,
                    ]),
            ],
            'bins' => BinLocation::where('branch_id', $order->branch_id)
                ->where('is_active', true)->orderBy('code')->get(['id', 'code']),
        ]);
    }

    public function store(Request $request, PurchaseOrder $order, GrnPostingService $service): RedirectResponse
    {
        $data = $request->validate([
            'delivery_note_number' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'over_receipt_approved' => ['sometimes', 'boolean'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.po_line_id' => ['required', 'integer'],
            'lines.*.qty_received' => ['required', 'numeric', 'gte:0'],
            'lines.*.qty_rejected' => ['nullable', 'numeric', 'gte:0'],
            'lines.*.rejection_reason' => ['nullable', 'string', 'max:255'],
            'lines.*.bin_location_id' => ['nullable', 'exists:bin_locations,id'],
        ]);

        $grn = $service->receive(
            $order,
            $data['lines'],
            $data['delivery_note_number'] ?? null,
            $request->user()->id,
            (bool) ($data['over_receipt_approved'] ?? false),
            $data['notes'] ?? null,
        );

        return redirect()->route('purchasing.orders.show', $order)
            ->with('success', "GRN {$grn->grn_number} posted — stock and GL updated.");
    }
}
