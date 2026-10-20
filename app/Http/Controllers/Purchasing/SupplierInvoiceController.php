<?php

namespace App\Http\Controllers\Purchasing;

use App\Http\Controllers\Controller;
use App\Models\GoodsReceivedNote;
use App\Models\SupplierInvoice;
use App\Services\SupplierInvoiceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SupplierInvoiceController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('Purchasing/Invoices/Index', [
            'invoices' => SupplierInvoice::with('supplier:id,name', 'grn:id,grn_number')
                ->when($request->string('status')->toString(), fn ($q, $s) => $q->where('status', $s))
                ->latest('invoice_date')->latest('id')
                ->paginate(25)
                ->withQueryString()
                ->through(fn (SupplierInvoice $inv) => [
                    'id' => $inv->id,
                    'invoice_number' => $inv->invoice_number,
                    'supplier_ref' => $inv->supplier_ref,
                    'supplier_id' => $inv->supplier_id,
                    'supplier' => $inv->supplier?->name,
                    'grn_id' => $inv->grn_id,
                    'grn_number' => $inv->grn?->grn_number,
                    'invoice_date' => $inv->invoice_date->toDateString(),
                    'due_date' => $inv->due_date?->toDateString(),
                    'total' => (float) $inv->total,
                    'status' => $inv->status,
                    'dispute_reason' => $inv->dispute_reason,
                ]),
            'uninvoicedGrns' => GoodsReceivedNote::with('supplier:id,name')
                ->where('status', 'posted')
                ->whereDoesntHave('lines', fn ($q) => $q->whereRaw('1=0')) // keep all
                ->whereNotIn('id', SupplierInvoice::whereIn('status', ['matched', 'posted'])->pluck('grn_id'))
                ->latest('received_date')->limit(10)->get()
                ->map(fn ($g) => [
                    'id' => $g->id, 'grn_number' => $g->grn_number,
                    'supplier' => $g->supplier?->name, 'value' => $g->acceptedValue(),
                ]),
            'filters' => $request->only('status'),
        ]);
    }

    public function create(GoodsReceivedNote $grn): Response
    {
        $grn->load('supplier:id,name,payment_terms_days', 'purchaseOrder:id,po_number', 'lines.part:id,part_number');

        return Inertia::render('Purchasing/Invoices/Create', [
            'grn' => [
                'id' => $grn->id,
                'grn_number' => $grn->grn_number,
                'po_id' => $grn->po_id,
                'po_number' => $grn->purchaseOrder?->po_number,
                'supplier_id' => $grn->supplier_id,
                'supplier' => $grn->supplier?->name,
                'received_date' => $grn->received_date->toDateString(),
                'value' => $grn->acceptedValue(),
                'lines' => $grn->lines->map(fn ($l) => [
                    'part_id' => $l->part_id,
                    'part_number' => $l->part?->part_number,
                    'qty_received' => (float) $l->qty_received,
                    'unit_cost' => (float) $l->unit_cost,
                    'line_total' => round((float) $l->qty_received * (float) $l->unit_cost, 2),
                ]),
            ],
        ]);
    }

    public function store(Request $request, GoodsReceivedNote $grn, SupplierInvoiceService $service): RedirectResponse
    {
        $data = $request->validate([
            'supplier_ref' => ['required', 'string', 'max:100'],
            'invoice_date' => ['required', 'date', 'before_or_equal:today'],
            'subtotal' => ['required', 'numeric', 'gt:0'],
            'vat_amount' => ['required', 'numeric', 'gte:0'],
        ]);

        $invoice = $service->capture(
            $grn,
            $data['supplier_ref'],
            $data['invoice_date'],
            (float) $data['subtotal'],
            (float) $data['vat_amount'],
            $request->user()->id,
        );

        return redirect()->route('purchasing.invoices.index')->with(
            $invoice->status === 'posted' ? 'success' : 'warning',
            $invoice->status === 'posted'
                ? "Invoice {$invoice->supplier_ref} matched and posted to accounts payable."
                : "Invoice {$invoice->supplier_ref} captured as DISPUTED: {$invoice->dispute_reason}"
        );
    }

    public function show(SupplierInvoice $supplierInvoice): Response
    {
        $supplierInvoice->load([
            'supplier:id,name,payment_terms_days', 'glJournal:id,journal_number',
            'grn:id,grn_number,po_id,branch_id,received_date,status',
            'grn.purchaseOrder:id,po_number,status', 'grn.branch:id,name',
            'grn.lines.part:id,part_number,description',
            'allocations.payment:id,payment_number,payment_date,method,amount,reference',
        ]);

        $grn = $supplierInvoice->grn;
        $paid = $supplierInvoice->paidAmount();

        return Inertia::render('Purchasing/Invoices/Show', [
            'invoice' => [
                'id' => $supplierInvoice->id,
                'invoice_number' => $supplierInvoice->invoice_number,
                'supplier_ref' => $supplierInvoice->supplier_ref,
                'status' => $supplierInvoice->status,
                'dispute_reason' => $supplierInvoice->dispute_reason,
                'invoice_date' => $supplierInvoice->invoice_date->toDateString(),
                'due_date' => $supplierInvoice->due_date?->toDateString(),
                'posted_at' => $supplierInvoice->posted_at?->toDateTimeString(),
                'subtotal' => (float) $supplierInvoice->subtotal,
                'vat_amount' => (float) $supplierInvoice->vat_amount,
                'total' => (float) $supplierInvoice->total,
                'paid' => $paid,
                'outstanding' => round((float) $supplierInvoice->total - $paid, 2),
                'supplier_id' => $supplierInvoice->supplier_id,
                'supplier' => $supplierInvoice->supplier?->name,
                'gl_journal_id' => $supplierInvoice->gl_journal_id,
                'journal_number' => $supplierInvoice->glJournal?->journal_number,
                'grn_id' => $supplierInvoice->grn_id,
                'grn_number' => $grn?->grn_number,
                'grn_received_date' => $grn?->received_date?->toDateString(),
                'grn_value' => $grn?->acceptedValue() ?? 0.0,
                'po_id' => $grn?->po_id,
                'po_number' => $grn?->purchaseOrder?->po_number,
                'branch_id' => $grn?->branch_id,
                'branch' => $grn?->branch?->name,
                'lines' => ($grn?->lines ?? collect())->map(fn ($l) => [
                    'id' => $l->id,
                    'part_id' => $l->part_id,
                    'part_number' => $l->part?->part_number,
                    'description' => $l->part?->description,
                    'qty_received' => (float) $l->qty_received,
                    'unit_cost' => (float) $l->unit_cost,
                    'line_total' => round((float) $l->qty_received * (float) $l->unit_cost, 2),
                ]),
                'payments' => $supplierInvoice->allocations
                    ->filter(fn ($a) => $a->payment !== null)
                    ->map(fn ($a) => [
                        'id' => $a->payment->id,
                        'payment_number' => $a->payment->payment_number,
                        'payment_date' => $a->payment->payment_date->toDateString(),
                        'method' => $a->payment->method,
                        'reference' => $a->payment->reference,
                        'allocated' => (float) $a->amount,
                    ])->values(),
            ],
        ]);
    }

    /** Resolve a dispute by posting anyway (after buyer approval). */
    public function post(Request $request, SupplierInvoice $invoice, SupplierInvoiceService $service): RedirectResponse
    {
        abort_unless($invoice->status === 'disputed', 422);

        $service->post($invoice, $request->user()->id);

        return back()->with('success', "Invoice {$invoice->supplier_ref} dispute resolved and posted.");
    }
}
