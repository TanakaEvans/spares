<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\SalesDocument;
use App\Services\DocumentPdfService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class InvoiceController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('Sales/Invoices/Index', [
            'invoices' => SalesDocument::query()
                ->where('document_type', 'invoice')
                ->with('customer:id,name', 'branch:id,name')
                ->when($request->string('search')->toString(), fn ($q, $s) => $q->where(
                    fn ($x) => $x->where('document_number', 'like', "%{$s}%")
                        ->orWhereHas('customer', fn ($y) => $y->where('name', 'like', "%{$s}%"))
                ))
                ->latest('document_date')->latest('id')
                ->paginate(25)
                ->withQueryString()
                ->through(fn (SalesDocument $d) => [
                    'id' => $d->id,
                    'document_number' => $d->document_number,
                    'customer' => $d->customer?->name,
                    'customer_id' => $d->customer_id,
                    'branch' => $d->branch?->name,
                    'document_date' => $d->document_date->toDateString(),
                    'total_incl' => (float) $d->total_incl,
                    'status' => $d->status,
                ]),
            'filters' => $request->only('search'),
        ]);
    }

    public function show(SalesDocument $invoice): Response
    {
        abort_unless($invoice->document_type === 'invoice', 404);

        $invoice->load('customer:id,name,customer_number', 'branch:id,name',
            'salesperson:id,name', 'lines.part:id,part_number', 'payments',
            'children:id,document_number,document_type,parent_id,total_incl,document_date');

        return Inertia::render('Sales/Invoices/Show', [
            'invoice' => $this->present($invoice),
            'creditNotes' => $invoice->children
                ->where('document_type', 'credit_note')
                ->map(fn ($c) => [
                    'id' => $c->id, 'document_number' => $c->document_number,
                    'document_date' => $c->document_date->toDateString(), 'total_incl' => (float) $c->total_incl,
                ])->values(),
        ]);
    }

    public function print(SalesDocument $invoice, DocumentPdfService $pdf): HttpResponse
    {
        abort_unless($invoice->document_type === 'invoice', 404);
        $invoice->load('customer', 'branch', 'lines.part', 'payments', 'salesperson');

        return $pdf->render('print.sales-invoice', ['invoice' => $invoice], $invoice->branch_id)
            ->stream("{$invoice->document_number}.pdf");
    }

    /** 80mm thermal receipt (Module 2.4). */
    public function receipt(SalesDocument $invoice, DocumentPdfService $pdf): HttpResponse
    {
        abort_unless($invoice->document_type === 'invoice', 404);
        $invoice->load('customer', 'branch', 'lines', 'payments');

        return $pdf->render('print.sales-receipt', ['invoice' => $invoice], $invoice->branch_id, paper: [0, 0, 226.77, 800])
            ->stream("{$invoice->document_number}-receipt.pdf");
    }

    private function present(SalesDocument $invoice): array
    {
        return [
            'id' => $invoice->id,
            'document_number' => $invoice->document_number,
            'status' => $invoice->status,
            'customer' => $invoice->customer?->only(['id', 'name', 'customer_number']),
            'branch' => $invoice->branch?->name,
            'salesperson' => $invoice->salesperson?->name,
            'document_date' => $invoice->document_date->toDateString(),
            'subtotal_excl' => (float) $invoice->subtotal_excl,
            'discount_amount' => (float) $invoice->discount_amount,
            'vat_amount' => (float) $invoice->vat_amount,
            'total_incl' => (float) $invoice->total_incl,
            'lines' => $invoice->lines->map(fn ($l) => [
                'id' => $l->id,
                'part_id' => $l->part_id,
                'part_number' => $l->part?->part_number,
                'description' => $l->description,
                'qty' => (float) $l->qty,
                'unit_price' => (float) $l->unit_price,
                'discount_pct' => (float) $l->discount_pct,
                'vat_amount' => (float) $l->vat_amount,
                'line_total_incl' => (float) $l->line_total_incl,
                'qty_credited' => (float) $l->qty_credited,
                'qty_creditable' => (float) $l->qty - (float) $l->qty_credited,
            ]),
            'payments' => $invoice->payments->map(fn ($p) => [
                'method' => $p->method,
                'amount' => (float) $p->amount,
                'tendered' => $p->tendered !== null ? (float) $p->tendered : null,
                'change_given' => (float) $p->change_given,
                'reference' => $p->reference,
            ]),
        ];
    }
}
