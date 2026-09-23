<?php

namespace App\Http\Controllers\Sales;

use App\Exceptions\DomainException;
use App\Http\Controllers\Controller;
use App\Models\SalesDocument;
use App\Services\DocumentPdfService;
use App\Services\SalesPostingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class CreditNoteController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('Sales/CreditNotes/Index', [
            'creditNotes' => SalesDocument::query()
                ->where('document_type', 'credit_note')
                ->with('customer:id,name', 'parent:id,document_number')
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
                    'against' => $d->parent?->document_number,
                    'document_date' => $d->document_date->toDateString(),
                    'total_incl' => (float) $d->total_incl,
                    'credit_mode' => $d->credit_mode,
                ]),
            'filters' => $request->only('search'),
        ]);
    }

    /** Build a credit note against a posted invoice. */
    public function create(SalesDocument $invoice): Response
    {
        abort_unless($invoice->document_type === 'invoice' && $invoice->status === 'posted', 404);
        $invoice->load('customer:id,name,customer_number', 'lines.part:id,part_number');

        return Inertia::render('Sales/CreditNotes/Create', [
            'invoice' => [
                'id' => $invoice->id,
                'document_number' => $invoice->document_number,
                'customer' => $invoice->customer?->only(['id', 'name', 'customer_number']),
                'document_date' => $invoice->document_date->toDateString(),
                'lines' => $invoice->lines->map(fn ($l) => [
                    'id' => $l->id,
                    'part_number' => $l->part?->part_number,
                    'description' => $l->description,
                    'qty' => (float) $l->qty,
                    'unit_price' => (float) $l->unit_price,
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
                'branch' => $creditNote->branch?->name,
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
