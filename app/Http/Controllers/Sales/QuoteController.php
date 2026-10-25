<?php

namespace App\Http\Controllers\Sales;

use App\Exceptions\DomainException;
use App\Http\Controllers\Concerns\ListQuery;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\SalesDocument;
use App\Services\DocumentPdfService;
use App\Services\SalesQuoteService;
use App\Services\SettingsService;
use App\Support\ListExport;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class QuoteController extends Controller
{
    use ListQuery;

    private function quotesQuery(Request $request): Builder
    {
        return $this->listApply(
            SalesDocument::query()
                ->where('document_type', 'quotation')
                ->with('customer:id,name')
                ->when($request->string('status')->toString(), fn ($q, $s) => $q->where('status', $s)),
            $request,
            [
                'searchable' => ['document_number', 'notes', 'customer.name'],
                'sortable' => [
                    'document_number' => 'document_number', 'document_date' => 'document_date',
                    'expiry_date' => 'expiry_date', 'total_incl' => 'total_incl', 'status' => 'status',
                ],
                'dateColumn' => 'document_date',
                'defaultSort' => ['document_date' => 'desc', 'id' => 'desc'],
            ]
        );
    }

    /** The filtered quotation list as CSV or branded PDF (same filters as the grid). */
    public function export(Request $request): HttpResponse
    {
        $rows = $this->quotesQuery($request)->limit(5000)->get()->map(fn (SalesDocument $d) => [
            $d->document_number, $d->customer?->name, $d->document_date?->toDateString(),
            $d->expiry_date?->toDateString() ?? '—',
            ucwords(str_replace('_', ' ', $d->isExpired() && $d->status === 'open' ? 'expired' : $d->status)),
            number_format((float) $d->total_incl, 2, '.', ''),
        ]);

        return ListExport::respond($request, 'quotations', 'Quotations',
            ['Quote No', 'Customer', 'Quoted', 'Expires', 'Status', 'Total'], $rows);
    }

    public function index(Request $request): Response
    {
        return Inertia::render('Sales/Quotes/Index', [
            'quotes' => $this->quotesQuery($request)
                ->paginate(25)
                ->withQueryString()
                ->through(fn (SalesDocument $d) => [
                    'id' => $d->id,
                    'document_number' => $d->document_number,
                    'customer' => $d->customer?->name,
                    'customer_id' => $d->customer_id,
                    'document_date' => $d->document_date->toDateString(),
                    'expiry_date' => $d->expiry_date?->toDateString(),
                    'days_left' => $d->expiry_date ? (int) now()->startOfDay()->diffInDays($d->expiry_date, false) : null,
                    'total_incl' => (float) $d->total_incl,
                    'status' => $d->isExpired() && $d->status === 'open' ? 'expired' : $d->status,
                ]),
            'stats' => [
                'open' => (int) SalesDocument::query()->where('document_type', 'quotation')->where('status', 'open')
                    ->where(fn ($q) => $q->whereNull('expiry_date')->orWhere('expiry_date', '>=', now()->toDateString()))->count(),
                'open_value' => round((float) SalesDocument::query()->where('document_type', 'quotation')->where('status', 'open')
                    ->where(fn ($q) => $q->whereNull('expiry_date')->orWhere('expiry_date', '>=', now()->toDateString()))->sum('total_incl'), 2),
                'expiring' => (int) SalesDocument::query()->where('document_type', 'quotation')->where('status', 'open')
                    ->whereBetween('expiry_date', [now()->toDateString(), now()->addDays(5)->toDateString()])->count(),
            ],
            'filters' => $this->listFilters($request, ['status']),
        ]);
    }

    public function create(SettingsService $settings): Response
    {
        return Inertia::render('Sales/Quotes/Create', [
            'walkIn' => Customer::walkIn()->only(['id', 'name', 'customer_number']),
            'vatRate' => (float) $settings->get('tax.vat_rate_default'),
            'expiryDays' => (int) $settings->get('sales.quote_expiry_days'),
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

        $branch = Branch::where('is_main_branch', true)->first() ?? Branch::firstOrFail();
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
        $quote->load('customer:id,name,customer_number', 'branch:id,name', 'lines.part:id,part_number',
            'children:id,document_number,document_type,parent_id',
            'children.children:id,document_number,document_type,parent_id');

        $order = $quote->children->firstWhere('document_type', 'order');
        $invoice = $order?->children->firstWhere('document_type', 'invoice');

        return Inertia::render('Sales/Quotes/Show', [
            'quote' => [
                'id' => $quote->id,
                'document_number' => $quote->document_number,
                'branch' => $quote->branch?->only(['id', 'name']),
                'status' => $quote->isExpired() && $quote->status === 'open' ? 'expired' : $quote->status,
                'customer' => $quote->customer?->only(['id', 'name', 'customer_number']),
                'document_date' => $quote->document_date->toDateString(),
                'expiry_date' => $quote->expiry_date?->toDateString(),
                'is_expired' => $quote->isExpired(),
                'subtotal_excl' => (float) $quote->subtotal_excl,
                'vat_amount' => (float) $quote->vat_amount,
                'total_incl' => (float) $quote->total_incl,
                'order' => $order?->only(['id', 'document_number']),
                'invoice' => $invoice?->only(['id', 'document_number']),
                'lines' => $quote->lines->map(fn ($l) => [
                    'id' => $l->id,
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
        } catch (DomainException $e) {
            return back()->withErrors(['convert' => $e->userMessage()]);
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
