<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Concerns\ListQuery;
use App\Http\Controllers\Controller;
use App\Models\GlJournal;
use App\Models\JobCard;
use App\Models\ReceiptAllocation;
use App\Models\SalesDocument;
use App\Services\DocumentPdfService;
use App\Support\ListExport;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class InvoiceController extends Controller
{
    use ListQuery;

    private function invoicesQuery(Request $request): Builder
    {
        return $this->listApply(
            SalesDocument::query()
                ->where('document_type', 'invoice')
                ->with('customer:id,name', 'branch:id,name', 'payments:id,document_id,method'),
            $request,
            [
                'searchable' => ['document_number', 'customer.name'],
                'sortable' => [
                    'document_number' => 'document_number', 'document_date' => 'document_date',
                    'total_incl' => 'total_incl', 'status' => 'status',
                ],
                'dateColumn' => 'document_date',
                'defaultSort' => ['document_date' => 'desc', 'id' => 'desc'],
            ]
        );
    }

    /** The filtered invoice list as CSV or branded PDF (same filters as the grid). */
    public function export(Request $request): HttpResponse
    {
        $rows = $this->invoicesQuery($request)->limit(5000)->get()->map(fn (SalesDocument $d) => [
            $d->document_number, $d->customer?->name, $d->branch?->name, $d->document_date?->toDateString(),
            ucwords(str_replace('_', ' ', $d->status)),
            $d->payments->contains(fn ($p) => $p->method === 'account') ? 'account' : 'paid',
            number_format((float) $d->total_incl, 2, '.', ''),
        ]);

        return ListExport::respond($request, 'tax-invoices', 'Tax Invoices',
            ['Invoice No', 'Customer', 'Branch', 'Date', 'Status', 'Tender', 'Total'], $rows);
    }

    public function index(Request $request): Response
    {
        return Inertia::render('Sales/Invoices/Index', [
            'invoices' => $this->invoicesQuery($request)
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
                    'on_account' => $d->payments->contains(fn ($p) => $p->method === 'account'),
                ]),
            'stats' => [
                'today_count' => (int) SalesDocument::query()->where('document_type', 'invoice')
                    ->whereDate('document_date', now()->toDateString())->count(),
                'today_total' => round((float) SalesDocument::query()->where('document_type', 'invoice')
                    ->whereDate('document_date', now()->toDateString())->sum('total_incl'), 2),
                'on_account' => (int) SalesDocument::query()->where('document_type', 'invoice')
                    ->whereHas('payments', fn ($p) => $p->where('method', 'account'))->count(),
            ],
            'filters' => $this->listFilters($request),
        ]);
    }

    public function show(SalesDocument $invoice): Response
    {
        abort_unless($invoice->document_type === 'invoice', 404);

        $invoice->load('customer:id,name,customer_number', 'branch:id,name',
            'salesperson:id,name', 'lines.part:id,part_number', 'payments',
            'parent:id,document_number,document_type,parent_id', 'parent.parent:id,document_number,document_type',
            'children:id,document_number,document_type,parent_id,total_incl,document_date');

        return Inertia::render('Sales/Invoices/Show', [
            'invoice' => $this->present($invoice),
            'creditNotes' => $invoice->children
                ->where('document_type', 'credit_note')
                ->map(fn ($c) => [
                    'id' => $c->id, 'document_number' => $c->document_number,
                    'document_date' => $c->document_date->toDateString(), 'total_incl' => (float) $c->total_incl,
                ])->values(),
            'related' => $this->related($invoice),
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
        $invoice->load('customer', 'branch', 'lines', 'payments', 'salesperson');

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
            'branch_id' => $invoice->branch_id,
            'salesperson' => $invoice->salesperson?->name,
            'salesperson_id' => $invoice->salesperson_id,
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

    /**
     * Everything this invoice is connected to (UI-13): the quote→order chain it
     * came from, the credit notes and receipts raised against it, its GL
     * journal and the workshop job that produced it.
     *
     * @return array<string,mixed>
     */
    private function related(SalesDocument $invoice): array
    {
        $order = $invoice->parent?->document_type === 'order' ? $invoice->parent : null;
        $quote = $order?->parent?->document_type === 'quotation'
            ? $order->parent
            : ($invoice->parent?->document_type === 'quotation' ? $invoice->parent : null);

        $receipts = ReceiptAllocation::query()
            ->where('document_id', $invoice->id)
            ->with('receipt:id,receipt_number,receipt_date,amount')
            ->get()
            ->map(fn (ReceiptAllocation $a) => [
                'id' => $a->receipt_id,
                'receipt_number' => $a->receipt?->receipt_number,
                'receipt_date' => $a->receipt?->receipt_date?->toDateString(),
                'amount' => (float) $a->amount,
            ])
            ->filter(fn ($r) => $r['receipt_number'] !== null)
            ->values();

        $journal = $invoice->gl_journal_id
            ? GlJournal::query()->find($invoice->gl_journal_id, ['id', 'journal_number'])
            : null;

        $job = JobCard::query()
            ->where('sales_document_id', $invoice->id)
            ->first(['id', 'job_number', 'vehicle_id', 'technician_id']);

        return [
            'quote' => $quote?->only(['id', 'document_number']),
            'order' => $order?->only(['id', 'document_number']),
            'receipts' => $receipts,
            'journal' => $journal?->only(['id', 'journal_number']),
            'job' => $job?->only(['id', 'job_number']),
        ];
    }
}
