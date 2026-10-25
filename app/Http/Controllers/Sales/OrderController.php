<?php

namespace App\Http\Controllers\Sales;

use App\Exceptions\DomainException;
use App\Http\Controllers\Concerns\ListQuery;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\SalesDocument;
use App\Models\StockLevel;
use App\Services\SalesPostingService;
use App\Services\SalesQuoteService;
use App\Services\SettingsService;
use App\Support\ListExport;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class OrderController extends Controller
{
    use ListQuery;
    private function ordersQuery(Request $request): Builder
    {
        return $this->listApply(
            SalesDocument::query()
                ->where('document_type', 'order')
                ->with('customer:id,name')
                ->when($request->string('status')->toString(), fn ($q, $s) => $q->where('status', $s)),
            $request,
            [
                'searchable' => ['document_number', 'notes', 'customer.name'],
                'sortable' => [
                    'document_number' => 'document_number', 'document_date' => 'document_date',
                    'total_incl' => 'total_incl', 'status' => 'status',
                ],
                'dateColumn' => 'document_date',
                'defaultSort' => ['document_date' => 'desc', 'id' => 'desc'],
            ]
        );
    }

    /** The filtered order list as CSV or branded PDF (same filters as the grid). */
    public function export(Request $request): HttpResponse
    {
        $rows = $this->ordersQuery($request)->limit(5000)->get()->map(fn (SalesDocument $d) => [
            $d->document_number, $d->customer?->name, $d->document_date?->toDateString(),
            ucwords(str_replace('_', ' ', $d->status)),
            number_format((float) $d->total_incl, 2, '.', ''),
        ]);

        return ListExport::respond($request, 'sales-orders', 'Sales Orders',
            ['Order No', 'Customer', 'Ordered', 'Status', 'Total'], $rows);
    }

    public function index(Request $request): Response
    {
        return Inertia::render('Sales/Orders/Index', [
            'orders' => $this->ordersQuery($request)
                ->paginate(25)
                ->withQueryString()
                ->through(fn (SalesDocument $d) => [
                    'id' => $d->id,
                    'document_number' => $d->document_number,
                    'customer' => $d->customer?->name,
                    'customer_id' => $d->customer_id,
                    'document_date' => $d->document_date->toDateString(),
                    'total_incl' => (float) $d->total_incl,
                    'status' => $d->status,
                ]),
            'stats' => $this->stats(),
            'filters' => $this->listFilters($request, ['status']),
        ]);
    }

    /** Phone/counter order capture — the same line builder as a quotation. */
    public function create(SettingsService $settings): Response
    {
        return Inertia::render('Sales/Orders/Create', [
            'walkIn' => Customer::walkIn()->only(['id', 'name', 'customer_number']),
            'vatRate' => (float) $settings->get('tax.vat_rate_default'),
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
            $order = $quotes->createOrder($customer, $branch->id, $data['lines'], $request->user()->id);
        } catch (DomainException $e) {
            return back()->withErrors(['lines' => $e->userMessage()]);
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['lines' => $e->getMessage()]);
        }

        return redirect()->route('sales.orders.show', $order)
            ->with('success', "Order {$order->document_number} created and stock reserved.");
    }

    public function show(SalesDocument $order): Response
    {
        abort_unless($order->document_type === 'order', 404);
        $order->load('customer:id,name,customer_number,is_walk_in,credit_limit,on_hold', 'branch:id,name', 'lines.part:id,part_number',
            'parent:id,document_number,document_type', 'children:id,document_number,document_type,parent_id');

        // Picking context: where each reserved line lives and what's free on the shelf.
        $levels = StockLevel::with('binLocation:id,code')
            ->where('branch_id', $order->branch_id)
            ->whereIn('part_id', $order->lines->pluck('part_id'))
            ->get()->keyBy('part_id');

        $customer = $order->customer;

        return Inertia::render('Sales/Orders/Show', [
            'order' => [
                'id' => $order->id,
                'document_number' => $order->document_number,
                'status' => $order->status,
                'customer' => $customer ? [
                    'id' => $customer->id,
                    'name' => $customer->name,
                    'customer_number' => $customer->customer_number,
                    'is_walk_in' => $customer->is_walk_in,
                    'on_hold' => $customer->on_hold,
                    'ar_balance' => $customer->arBalance(),
                    'available_credit' => round((float) $customer->credit_limit - $customer->arBalance(), 2),
                ] : null,
                'document_date' => $order->document_date->toDateString(),
                'branch' => $order->branch?->only(['id', 'name']),
                'from_quote' => $order->parent?->document_type === 'quotation' ? $order->parent->only(['id', 'document_number']) : null,
                'invoice' => $order->children->firstWhere('document_type', 'invoice')?->only(['id', 'document_number']),
                'subtotal_excl' => (float) $order->subtotal_excl,
                'vat_amount' => (float) $order->vat_amount,
                'total_incl' => (float) $order->total_incl,
                'is_fulfillable' => $order->status === 'confirmed',
                'lines' => $order->lines->map(function ($l) use ($levels) {
                    $level = $levels->get($l->part_id);

                    return [
                        'part_id' => $l->part_id,
                        'part_number' => $l->part?->part_number,
                        'description' => $l->description,
                        'qty' => (float) $l->qty,
                        'unit_price' => (float) $l->unit_price,
                        'discount_pct' => (float) $l->discount_pct,
                        'line_total_incl' => (float) $l->line_total_incl,
                        'bin' => $level?->binLocation?->code,
                        'on_hand' => (float) ($level->qty_on_hand ?? 0),
                    ];
                }),
            ],
        ]);
    }

    /** Header strip: work waiting on the counter. */
    private function stats(): array
    {
        $open = SalesDocument::query()->where('document_type', 'order')->where('status', 'confirmed');

        return [
            'awaiting' => (int) $open->clone()->count(),
            'awaiting_value' => round((float) $open->clone()->sum('total_incl'), 2),
            'invoiced_today' => (int) SalesDocument::query()->where('document_type', 'order')
                ->where('status', 'invoiced')->whereDate('updated_at', now()->toDateString())->count(),
        ];
    }

    /** Fulfil the order → post the tax invoice, releasing reservations. */
    public function fulfil(Request $request, SalesDocument $order, SalesPostingService $sales): RedirectResponse
    {
        abort_unless($order->document_type === 'order', 404);

        if ($order->status !== 'confirmed') {
            return back()->withErrors(['fulfil' => "Order {$order->document_number} is {$order->status}."]);
        }

        $data = $request->validate([
            'payments' => ['required', 'array', 'min:1'],
            'payments.*.method' => ['required', 'in:cash,card,eft,account'],
            'payments.*.amount' => ['required', 'numeric', 'gt:0'],
            'payments.*.tendered' => ['nullable', 'numeric', 'min:0'],
            'payments.*.reference' => ['nullable', 'string', 'max:100'],
        ]);

        $order->load('lines');
        $lines = $order->lines->map(fn ($l) => [
            'part_id' => $l->part_id,
            'qty' => (float) $l->qty,
            'unit_price' => (float) $l->unit_price,
            'discount_pct' => (float) $l->discount_pct,
            'description' => $l->description,
        ])->all();

        try {
            $invoice = $sales->postInvoice(
                $order->customer, $order->branch_id, $lines, $data['payments'], $request->user()->id, $order
            );
        } catch (DomainException $e) {
            return back()->withErrors(['fulfil' => $e->userMessage()]);
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['fulfil' => $e->getMessage()]);
        }

        return redirect()->route('sales.invoices.show', $invoice)
            ->with('success', "Order fulfilled — invoice {$invoice->document_number} posted.");
    }

    public function cancel(Request $request, SalesDocument $order, SalesQuoteService $quotes): RedirectResponse
    {
        abort_unless($order->document_type === 'order', 404);

        try {
            $quotes->cancelOrder($order, $request->user()->id);
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['cancel' => $e->getMessage()]);
        }

        return back()->with('success', "Order {$order->document_number} cancelled and reservations released.");
    }
}
