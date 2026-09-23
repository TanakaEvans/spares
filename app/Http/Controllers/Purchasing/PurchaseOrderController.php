<?php

namespace App\Http\Controllers\Purchasing;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Part;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\SupplierPriceListItem;
use App\Services\DocumentPdfService;
use App\Services\NumberSequenceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class PurchaseOrderController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('Purchasing/Orders/Index', [
            'orders' => PurchaseOrder::with('supplier:id,name', 'branch:id,name')
                ->when($request->string('status')->toString(), fn ($q, $s) => $q->where('status', $s))
                ->when($request->string('search')->toString(), fn ($q, $s) => $q->where(
                    fn ($x) => $x->where('po_number', 'like', "%{$s}%")
                        ->orWhereHas('supplier', fn ($y) => $y->where('name', 'like', "%{$s}%"))
                ))
                ->latest('order_date')->latest('id')
                ->paginate(25)
                ->withQueryString()
                ->through(fn (PurchaseOrder $po) => [
                    'id' => $po->id,
                    'po_number' => $po->po_number,
                    'supplier' => $po->supplier?->name,
                    'branch' => $po->branch?->name,
                    'status' => $po->status,
                    'order_date' => $po->order_date->toDateString(),
                    'expected_date' => $po->expected_date?->toDateString(),
                    'total' => (float) $po->total,
                ]),
            'filters' => $request->only('search', 'status'),
        ]);
    }

    public function create(Request $request): Response
    {
        return Inertia::render('Purchasing/Orders/Create', [
            'suppliers' => Supplier::active()->orderBy('name')->get(['id', 'name', 'lead_time_days']),
            'branches' => Branch::where('status', 'active')->get(['id', 'name']),
            'preselectedSupplierId' => $request->integer('supplier_id') ?: null,
        ]);
    }

    /** Part search for PO lines — returns supplier price-list cost when available. */
    public function partLookup(Request $request): \Illuminate\Http\JsonResponse
    {
        $q = trim((string) $request->query('q', ''));
        $supplierId = $request->integer('supplier_id') ?: null;

        if (mb_strlen($q) < 2) {
            return response()->json(['parts' => []]);
        }

        $parts = Part::search($q)->active()->with('stockLevels')->limit(8)->get()
            ->map(function (Part $p) use ($supplierId) {
                $listCost = $supplierId
                    ? SupplierPriceListItem::where('part_id', $p->id)
                        ->whereHas('priceList', fn ($x) => $x->where('supplier_id', $supplierId)->where('status', 'active'))
                        ->value('cost_price')
                    : null;

                return [
                    'id' => $p->id,
                    'part_number' => $p->part_number,
                    'description' => $p->description,
                    'suggested_cost' => $listCost !== null
                        ? (float) $listCost
                        : (float) ($p->stockLevels->first()?->average_cost ?? 0),
                    'cost_source' => $listCost !== null ? 'price list' : 'last AVCO',
                ];
            });

        return response()->json(['parts' => $parts]);
    }

    public function store(Request $request, NumberSequenceService $sequences): RedirectResponse
    {
        $data = $request->validate([
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'branch_id' => ['required', 'exists:branches,id'],
            'expected_date' => ['nullable', 'date'],
            'supplier_ref' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.part_id' => ['required', 'exists:parts,id'],
            'lines.*.qty' => ['required', 'numeric', 'gt:0'],
            'lines.*.unit_cost' => ['required', 'numeric', 'gte:0'],
        ]);

        $po = DB::transaction(function () use ($data, $request, $sequences) {
            $po = PurchaseOrder::create([
                'po_number' => $sequences->next('purchase_order', $data['branch_id']),
                'supplier_id' => $data['supplier_id'],
                'branch_id' => $data['branch_id'],
                'buyer_id' => $request->user()->id,
                'status' => 'draft',
                'order_date' => now()->toDateString(),
                'expected_date' => $data['expected_date'] ?? null,
                'supplier_ref' => $data['supplier_ref'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            $subtotal = 0.0;
            foreach ($data['lines'] as $line) {
                $part = Part::findOrFail($line['part_id']);
                $lineTotal = round((float) $line['qty'] * (float) $line['unit_cost'], 2);
                $po->lines()->create([
                    'part_id' => $part->id,
                    'description' => $part->description,
                    'qty_ordered' => $line['qty'],
                    'unit_cost' => $line['unit_cost'],
                    'line_total' => $lineTotal,
                ]);
                $subtotal += $lineTotal;
            }

            $po->update(['subtotal' => $subtotal, 'total' => $subtotal]);

            return $po;
        });

        return redirect()->route('purchasing.orders.show', $po)
            ->with('success', "Purchase order {$po->po_number} created as draft.");
    }

    public function show(PurchaseOrder $order): Response
    {
        $order->load(['supplier:id,name,email,lead_time_days', 'branch:id,name',
            'buyer:id,name', 'lines.part:id,part_number',
            'grns' => fn ($q) => $q->latest(),
        ]);

        return Inertia::render('Purchasing/Orders/Show', [
            'order' => [
                'id' => $order->id,
                'po_number' => $order->po_number,
                'status' => $order->status,
                'supplier' => $order->supplier?->only(['id', 'name', 'email']),
                'branch' => $order->branch?->name,
                'buyer' => $order->buyer?->name,
                'order_date' => $order->order_date->toDateString(),
                'expected_date' => $order->expected_date?->toDateString(),
                'supplier_ref' => $order->supplier_ref,
                'notes' => $order->notes,
                'subtotal' => (float) $order->subtotal,
                'total' => (float) $order->total,
                'is_editable' => $order->isEditable(),
                'is_receivable' => $order->isReceivable(),
                'lines' => $order->lines->map(fn ($l) => [
                    'id' => $l->id,
                    'part_id' => $l->part_id,
                    'part_number' => $l->part?->part_number,
                    'description' => $l->description,
                    'qty_ordered' => (float) $l->qty_ordered,
                    'qty_received' => (float) $l->qty_received,
                    'qty_outstanding' => (float) $l->qty_ordered - (float) $l->qty_received,
                    'unit_cost' => (float) $l->unit_cost,
                    'line_total' => (float) $l->line_total,
                ]),
                'grns' => $order->grns->map(fn ($g) => [
                    'id' => $g->id, 'grn_number' => $g->grn_number,
                    'received_date' => $g->received_date->toDateString(), 'status' => $g->status,
                ]),
            ],
        ]);
    }

    public function transition(Request $request, PurchaseOrder $order): RedirectResponse
    {
        $action = $request->validate(['action' => ['required', 'in:submit,confirm,cancel,close']])['action'];

        $allowed = match ($action) {
            'submit' => $order->status === 'draft',
            'confirm' => $order->status === 'submitted',
            'cancel' => in_array($order->status, ['draft', 'submitted'], true),
            'close' => in_array($order->status, ['partial', 'received'], true),
        };

        if (! $allowed) {
            return back()->withErrors(['action' => "Cannot {$action} a PO in status {$order->status}."]);
        }

        $order->update(['status' => match ($action) {
            'submit' => 'submitted',
            'confirm' => 'confirmed',
            'cancel' => 'cancelled',
            'close' => 'closed',
        }]);

        return back()->with('success', "PO {$order->po_number} ".match ($action) {
            'submit' => 'submitted to supplier.',
            'confirm' => 'confirmed by supplier.',
            'cancel' => 'cancelled.',
            'close' => 'closed — outstanding lines cancelled.',
        });
    }

    public function print(PurchaseOrder $order, DocumentPdfService $pdf): \Symfony\Component\HttpFoundation\Response
    {
        $order->load('supplier', 'lines.part', 'branch');

        return $pdf->render('print.purchase-order', ['order' => $order], $order->branch_id)
            ->stream("{$order->po_number}.pdf");
    }
}
