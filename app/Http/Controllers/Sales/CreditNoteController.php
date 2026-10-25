<?php

namespace App\Http\Controllers\Sales;

use App\Exceptions\DomainException;
use App\Http\Controllers\Concerns\ListQuery;
use App\Http\Controllers\Controller;
use App\Models\GlJournal;
use App\Models\SalesDocument;
use App\Services\DocumentPdfService;
use App\Services\SalesPostingService;
use App\Support\ListExport;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class CreditNoteController extends Controller
{
    use ListQuery;

    private function creditNotesQuery(Request $request): Builder
    {
        return $this->listApply(
            SalesDocument::query()
                ->where('document_type', 'credit_note')
                ->with('customer:id,name', 'parent:id,document_number'),
            $request,
            [
                'searchable' => ['document_number', 'reason', 'customer.name'],
                'sortable' => [
                    'document_number' => 'document_number', 'document_date' => 'document_date',
                    'total_incl' => 'total_incl', 'credit_mode' => 'credit_mode',
                ],
                'dateColumn' => 'document_date',
                'defaultSort' => ['document_date' => 'desc', 'id' => 'desc'],
            ]
        );
    }

    /** The filtered credit-note list as CSV or branded PDF (same filters as the grid). */
    public function export(Request $request): HttpResponse
    {
        $rows = $this->creditNotesQuery($request)->limit(5000)->get()->map(fn (SalesDocument $d) => [
            $d->document_number, $d->customer?->name, $d->parent?->document_number,
            $d->document_date?->toDateString(),
            ucwords(str_replace('_', ' ', $d->credit_mode ?? '')),
            number_format((float) $d->total_incl, 2, '.', ''),
        ]);

        return ListExport::respond($request, 'credit-notes', 'Credit Notes',
            ['CN No', 'Customer', 'Against', 'Date', 'Settled by', 'Total'], $rows);
    }

    public function index(Request $request): Response
    {
        return Inertia::render('Sales/CreditNotes/Index', [
            'creditNotes' => $this->creditNotesQuery($request)
                ->paginate(25)
                ->withQueryString()
                ->through(fn (SalesDocument $d) => [
                    'id' => $d->id,
                    'document_number' => $d->document_number,
                    'customer' => $d->customer?->name,
                    'customer_id' => $d->customer_id,
                    'against' => $d->parent?->document_number,
                    'against_id' => $d->parent_id,
                    'document_date' => $d->document_date->toDateString(),
                    'total_incl' => (float) $d->total_incl,
                    'credit_mode' => $d->credit_mode,
                ]),
            'stats' => [
                'month_count' => (int) SalesDocument::query()->where('document_type', 'credit_note')
                    ->whereBetween('document_date', [now()->startOfMonth()->toDateString(), now()->toDateString()])->count(),
                'month_total' => round((float) SalesDocument::query()->where('document_type', 'credit_note')
                    ->whereBetween('document_date', [now()->startOfMonth()->toDateString(), now()->toDateString()])->sum('total_incl'), 2),
            ],
            'filters' => $this->listFilters($request),
        ]);
    }

    /** Build a credit note against a posted invoice. */
    public function create(SalesDocument $invoice): Response
    {
        abort_unless($invoice->document_type === 'invoice' && $invoice->status === 'posted', 404);
        $invoice->load('customer:id,name,customer_number,is_walk_in', 'lines.part:id,part_number', 'payments:id,document_id,method,amount');

        return Inertia::render('Sales/CreditNotes/Create', [
            'invoice' => [
                'id' => $invoice->id,
                'document_number' => $invoice->document_number,
                'customer' => $invoice->customer?->only(['id', 'name', 'customer_number', 'is_walk_in']),
                'document_date' => $invoice->document_date->toDateString(),
                'total_incl' => (float) $invoice->total_incl,
                'total_credited' => round((float) $invoice->lines->sum('qty_credited') > 0
                    ? (float) $invoice->children()->where('document_type', 'credit_note')->sum('total_incl')
                    : 0, 2),
                'same_day' => $invoice->document_date->isToday(),
                'payments' => $invoice->payments->map(fn ($p) => [
                    'method' => $p->method,
                    'amount' => (float) $p->amount,
                ])->values(),
                'lines' => $invoice->lines->map(fn ($l) => [
                    'id' => $l->id,
                    'part_id' => $l->part_id,
                    'part_number' => $l->part?->part_number,
                    'description' => $l->description,
                    'qty' => (float) $l->qty,
                    'unit_price' => (float) $l->unit_price,
                    'discount_pct' => (float) $l->discount_pct,
                    'vat_rate' => (float) $l->vat_rate,
                    'qty_credited' => (float) $l->qty_credited,
                    'qty_creditable' => (float) $l->qty - (float) $l->qty_credited,
                ])->filter(fn ($l) => $l['qty_creditable'] > 0)->values(),
            ],
        ]);
    }

    public function store(Request $request, SalesDocument $invoice, SalesPostingService $sales): RedirectResponse
    {
        abort_unless($invoice->document_type === 'invoice', 404);

        $data = $request->validate([
            'mode' => ['required', 'in:refund_cash,account'],
            'reason' => ['required', 'string', 'max:255'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.line_id' => ['required', 'integer'],
            'lines.*.qty' => ['required', 'numeric', 'gt:0'],
            'lines.*.restock' => ['nullable', 'boolean'],
        ]);

        try {
            $credit = $sales->postCreditNote(
                $invoice,
                array_map(fn ($l) => [
                    'line_id' => (int) $l['line_id'],
                    'qty' => (float) $l['qty'],
                    'restock' => $l['restock'] ?? true,
                ], $data['lines']),
                $data['mode'],
                $data['reason'],
                $request->user()->id,
            );
        } catch (DomainException $e) {
            return back()->withErrors(['credit' => $e->userMessage()]);
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['credit' => $e->getMessage()]);
        }

        return redirect()->route('sales.credit-notes.show', $credit)
            ->with('success', "Credit note {$credit->document_number} posted.");
    }

    public function show(SalesDocument $creditNote): Response
    {
        abort_unless($creditNote->document_type === 'credit_note', 404);
        $creditNote->load('customer:id,name,customer_number', 'branch:id,name',
            'parent:id,document_number', 'lines.part:id,part_number');

        return Inertia::render('Sales/CreditNotes/Show', [
            'creditNote' => [
                'id' => $creditNote->id,
                'document_number' => $creditNote->document_number,
                'customer' => $creditNote->customer?->only(['id', 'name', 'customer_number']),
                'against' => $creditNote->parent?->only(['id', 'document_number']),
                'branch' => $creditNote->branch?->only(['id', 'name']),
                'journal' => $creditNote->gl_journal_id
                    ? GlJournal::query()->find($creditNote->gl_journal_id, ['id', 'journal_number'])?->only(['id', 'journal_number'])
                    : null,
                'document_date' => $creditNote->document_date->toDateString(),
                'credit_mode' => $creditNote->credit_mode,
                'reason' => $creditNote->reason,
                'subtotal_excl' => (float) $creditNote->subtotal_excl,
                'vat_amount' => (float) $creditNote->vat_amount,
                'total_incl' => (float) $creditNote->total_incl,
                'lines' => $creditNote->lines->map(fn ($l) => [
                    'part_id' => $l->part_id,
                    'part_number' => $l->part?->part_number,
                    'description' => $l->description,
                    'qty' => (float) $l->qty,
                    'unit_price' => (float) $l->unit_price,
                    'line_total_incl' => (float) $l->line_total_incl,
                ]),
            ],
        ]);
    }

    public function print(SalesDocument $creditNote, DocumentPdfService $pdf): HttpResponse
    {
        abort_unless($creditNote->document_type === 'credit_note', 404);
        $creditNote->load('customer', 'branch', 'lines.part', 'parent');

        return $pdf->render('print.sales-credit-note', ['creditNote' => $creditNote], $creditNote->branch_id)
            ->stream("{$creditNote->document_number}.pdf");
    }
}
