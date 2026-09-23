<?php

namespace App\Http\Controllers\Sales;

use App\Exceptions\DomainException;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\SalesDocument;
use App\Services\DocumentPdfService;
use App\Services\SalesQuoteService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class QuoteController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('Sales/Quotes/Index', [
            'quotes' => SalesDocument::query()
                ->where('document_type', 'quotation')
                ->with('customer:id,name')
                ->when($request->string('status')->toString(), fn ($q, $s) => $q->where('status', $s))
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
                    'document_date' => $d->document_date->toDateString(),
                    'expiry_date' => $d->expiry_date?->toDateString(),
                    'total_incl' => (float) $d->total_incl,
                    'status' => $d->isExpired() && $d->status === 'open' ? 'expired' : $d->status,
                ]),
            'filters' => $request->only('search', 'status'),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Sales/Quotes/Create', [
            'walkIn' => Customer::walkIn()->only(['id', 'name', 'customer_number']),
        ]);
    }

    public function store(Request $request, SalesQuoteService $quotes): RedirectResponse
    {
        $data = $request->validate([
            'customer_id' => ['required', 'exists:customers,id'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.part_id' => ['required', 'exists:parts,id'],
            'lines.*.qty' => ['required', 'numeric', 'gt:0'],
            'lines.*.unit_price' => ['nullable', 'numeric', 'min:0'],
            'lines.*.discount_pct' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        $branch = \App\Models\Branch::where('is_main_branch', true)->first() ?? \App\Models\Branch::firstOrFail();
        $customer = Customer::findOrFail($data['customer_id']);

        try {
            $quote = $quotes->createQuote($customer, $branch->id, $data['lines'], $request->user()->id);
        } catch (DomainException $e) {
            return back()->withErrors(['lines' => $e->userMessage()]);
        }

        return redirect()->route('sales.quotes.show', $quote)
            ->with('success', "Quote {$quote->document_number} created.");
    }

    public function show(SalesDocument $quote, SalesQuoteService $quotes): Response
    {
        abort_unless($quote->document_type === 'quotation', 404);
        $quote->load('customer:id,name,customer_number', 'lines.part:id,part_number', 'children:id,document_number,document_type,parent_id');

        return Inertia::render('Sales/Quotes/Show', [
            'quote' => [
                'id' => $quote->id,
                'document_number' => $quote->document_number,
                'status' => $quote->isExpired() && $quote->status === 'open' ? 'expired' : $quote->status,
                'customer' => $quote->customer?->only(['id', 'name', 'customer_number']),
                'document_date' => $quote->document_date->toDateString(),
                'expiry_date' => $quote->expiry_date?->toDateString(),
                'is_expired' => $quote->isExpired(),
                'subtotal_excl' => (float) $quote->subtotal_excl,
                'vat_amount' => (float) $quote->vat_amount,
                'total_incl' => (float) $quote->total_incl,
                'order' => $quote->children->firstWhere('document_type', 'order')?->only(['id', 'document_number']),
                'lines' => $quote->lines->map(fn ($l) => [
                    'part_id' => $l->part_id,
                    'part_number' => $l->part?->part_number,
                    'description' => $l->description,
                    'qty' => (float) $l->qty,
                    'unit_price' => (float) $l->unit_price,
                    'discount_pct' => (float) $l->discount_pct,
                    'line_total_incl' => (float) $l->line_total_incl,
                ]),
            ],
            'repriceChanges' => $quote->status === 'open' && ! $quote->isExpired()
                ? $quotes->repriceCheck($quote)
                : [],
        ]);
    }

    public function convert(Request $request, SalesDocument $quote, SalesQuoteService $quotes): RedirectResponse
    {
        abort_unless($quote->document_type === 'quotation', 404);
        $reprice = $request->boolean('reprice');

        try {
            $order = $quotes->convertToOrder($quote, $reprice, $request->user()->id);
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['convert' => $e->getMessage()]);
        }

        return redirect()->route('sales.orders.show', $order)
            ->with('success', "Order {$order->document_number} created and stock reserved.");
    }

    public function print(SalesDocument $quote, DocumentPdfService $pdf): HttpResponse
    {
        abort_unless($quote->document_type === 'quotation', 404);
        $quote->load('customer', 'branch', 'lines.part');

        return $pdf->render('print.sales-quote', ['quote' => $quote], $quote->branch_id)
            ->stream("{$quote->document_number}.pdf");
    }
}
