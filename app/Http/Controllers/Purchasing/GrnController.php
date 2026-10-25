<?php

namespace App\Http\Controllers\Purchasing;

use App\Http\Controllers\Concerns\ListQuery;
use App\Http\Controllers\Controller;
use App\Models\BinLocation;
use App\Models\GoodsReceivedNote;
use App\Models\GrnLine;
use App\Models\PurchaseOrder;
use App\Models\StockLevel;
use App\Models\SupplierInvoice;
use App\Services\GrnPostingService;
use App\Support\ListExport;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class GrnController extends Controller
{
    use ListQuery;
    private function grnsQuery(Request $request): Builder
    {
        return $this->listApply(
            GoodsReceivedNote::with('supplier:id,name', 'purchaseOrder:id,po_number', 'branch:id,name')
                ->when($request->string('status')->toString(), fn ($q, $s) => $q->where('status', $s)),
            $request,
            [
                'searchable' => ['grn_number', 'delivery_note_number', 'supplier.name', 'purchaseOrder.po_number'],
                'sortable' => [
                    'grn_number' => 'grn_number', 'received_date' => 'received_date',
                    'delivery_note_number' => 'delivery_note_number', 'status' => 'status',
                ],
                'dateColumn' => 'received_date',
                'defaultSort' => ['received_date' => 'desc', 'id' => 'desc'],
            ]
        );
    }

    /** The filtered GRN list as CSV or branded PDF (same filters as the grid). */
    public function export(Request $request): HttpResponse
    {
        $rows = $this->grnsQuery($request)->limit(5000)->get()->map(fn (GoodsReceivedNote $g) => [
            $g->grn_number, $g->purchaseOrder?->po_number, $g->supplier?->name, $g->branch?->name,
            $g->received_date?->toDateString(), $g->delivery_note_number,
            ucwords(str_replace('_', ' ', $g->status)),
            number_format($g->acceptedValue(), 2, '.', ''),
        ]);

        return ListExport::respond($request, 'goods-received-notes', 'Goods Received Notes',
            ['GRN No', 'PO No', 'Supplier', 'Branch', 'Received', 'Supplier DN', 'Status', 'Value'], $rows);
    }

    public function index(Request $request): Response
    {
        return Inertia::render('Purchasing/GRNs/Index', [
            'grns' => $this->grnsQuery($request)
                ->paginate(25)
                ->withQueryString()
                ->through(fn (GoodsReceivedNote $g) => [
                    'id' => $g->id,
                    'grn_number' => $g->grn_number,
                    'po_number' => $g->purchaseOrder?->po_number,
                    'po_id' => $g->po_id,
                    'supplier_id' => $g->supplier_id,
                    'supplier' => $g->supplier?->name,
                    'branch_id' => $g->branch_id,
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
                    'expected_date' => $po->expected_date?->toDateString(),
                    'is_overdue' => $po->expected_date !== null && $po->expected_date->isPast(),
                ]),
            'stats' => [
                'awaiting' => (int) PurchaseOrder::whereIn('status', ['submitted', 'confirmed', 'partial'])->count(),
                'today_count' => (int) GoodsReceivedNote::whereDate('received_date', now()->toDateString())->count(),
                'today_value' => round((float) GoodsReceivedNote::whereDate('received_date', now()->toDateString())
                    ->get()->sum(fn ($g) => $g->acceptedValue()), 2),
            ],
            'filters' => $this->listFilters($request, ['status']),
        ]);
    }

    public function create(PurchaseOrder $order): Response
    {
        abort_unless($order->isReceivable(), 422, 'This PO cannot be received against.');

        $order->load('supplier:id,name', 'lines.part:id,part_number');

        // The part's primary bin at this branch pre-fills putaway — the
        // storeman only corrects the exceptions.
        $levels = StockLevel::where('branch_id', $order->branch_id)
            ->whereIn('part_id', $order->lines->pluck('part_id'))
            ->get()->keyBy('part_id');

        return Inertia::render('Purchasing/GRNs/Create', [
            'order' => [
                'id' => $order->id,
                'po_number' => $order->po_number,
                'supplier_id' => $order->supplier_id,
                'supplier' => $order->supplier?->name,
                'branch_id' => $order->branch_id,
                'lines' => $order->lines
                    ->filter(fn ($l) => (float) $l->qty_received < (float) $l->qty_ordered * 1.5)
                    ->values()
                    ->map(fn ($l) => [
                        'po_line_id' => $l->id,
                        'part_id' => $l->part_id,
                        'part_number' => $l->part?->part_number,
                        'description' => $l->description,
                        'qty_ordered' => (float) $l->qty_ordered,
                        'qty_already_received' => (float) $l->qty_received,
                        'qty_outstanding' => max(0, (float) $l->qty_ordered - (float) $l->qty_received),
                        'unit_cost' => (float) $l->unit_cost,
                        'suggested_bin_id' => $levels->get($l->part_id)?->bin_location_id,
                    ]),
            ],
            'bins' => BinLocation::where('branch_id', $order->branch_id)
                ->where('is_active', true)->orderBy('code')->get(['id', 'code']),
        ]);
    }

    public function show(GoodsReceivedNote $grn): Response
    {
        $grn->load([
            'supplier:id,name', 'purchaseOrder:id,po_number,status', 'branch:id,name',
            'receivedBy:id,name', 'glJournal:id,journal_number',
            'lines.part:id,part_number,description', 'lines.bin:id,code',
            'invoices' => fn ($q) => $q->latest('id')->with('allocations.payment:id,payment_number,payment_date,amount'),
        ]);

        $payments = $grn->invoices
            ->flatMap(fn (SupplierInvoice $inv) => $inv->allocations->map(fn ($a) => $a->payment))
            ->filter()->unique('id')->values();

        return Inertia::render('Purchasing/GRNs/Show', [
            'grn' => [
                'id' => $grn->id,
                'grn_number' => $grn->grn_number,
                'status' => $grn->status,
                'received_date' => $grn->received_date->toDateString(),
                'posted_at' => $grn->posted_at?->toDateTimeString(),
                'delivery_note_number' => $grn->delivery_note_number,
                'notes' => $grn->notes,
                'po_id' => $grn->po_id,
                'po_number' => $grn->purchaseOrder?->po_number,
                'po_status' => $grn->purchaseOrder?->status,
                'supplier_id' => $grn->supplier_id,
                'supplier' => $grn->supplier?->name,
                'branch_id' => $grn->branch_id,
                'branch' => $grn->branch?->name,
                'received_by_id' => $grn->received_by,
                'received_by' => $grn->receivedBy?->name,
                'gl_journal_id' => $grn->gl_journal_id,
                'journal_number' => $grn->glJournal?->journal_number,
                'accepted_value' => $grn->acceptedValue(),
                'lines' => $grn->lines->map(fn (GrnLine $l) => [
                    'id' => $l->id,
                    'part_id' => $l->part_id,
                    'part_number' => $l->part?->part_number,
                    'description' => $l->part?->description,
                    'qty_received' => (float) $l->qty_received,
                    'qty_rejected' => (float) $l->qty_rejected,
                    'rejection_reason' => $l->rejection_reason,
                    'unit_cost' => (float) $l->unit_cost,
                    'line_total' => round((float) $l->qty_received * (float) $l->unit_cost, 2),
                    'bin_id' => $l->bin_location_id,
                    'bin' => $l->bin?->code,
                ]),
                'invoices' => $grn->invoices->map(fn (SupplierInvoice $inv) => [
                    'id' => $inv->id,
                    'invoice_number' => $inv->invoice_number,
                    'supplier_ref' => $inv->supplier_ref,
                    'status' => $inv->status,
                    'invoice_date' => $inv->invoice_date->toDateString(),
                    'total' => (float) $inv->total,
                ]),
                'payments' => $payments->map(fn ($p) => [
                    'id' => $p->id,
                    'payment_number' => $p->payment_number,
                    'payment_date' => $p->payment_date->toDateString(),
                    'amount' => (float) $p->amount,
                ]),
                'can_invoice' => $grn->status === 'posted'
                    && ! $grn->invoices->contains(fn ($inv) => in_array($inv->status, ['matched', 'posted'], true)),
            ],
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
