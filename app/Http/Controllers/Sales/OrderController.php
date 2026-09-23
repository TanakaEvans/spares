<?php

namespace App\Http\Controllers\Sales;

use App\Exceptions\DomainException;
use App\Http\Controllers\Controller;
use App\Models\SalesDocument;
use App\Services\SalesPostingService;
use App\Services\SalesQuoteService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OrderController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('Sales/Orders/Index', [
            'orders' => SalesDocument::query()
                ->where('document_type', 'order')
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
                    'total_incl' => (float) $d->total_incl,
                    'status' => $d->status,
                ]),
            'filters' => $request->only('search', 'status'),
        ]);
    }

    public function show(SalesDocument $order): Response
    {
        abort_unless($order->document_type === 'order', 404);
        $order->load('customer:id,name,customer_number,is_walk_in', 'lines.part:id,part_number',
            'parent:id,document_number', 'children:id,document_number,document_type,parent_id');

        return Inertia::render('Sales/Orders/Show', [
            'order' => [
                'id' => $order->id,
                'document_number' => $order->document_number,
                'status' => $order->status,
                'customer' => $order->customer?->only(['id', 'name', 'customer_number', 'is_walk_in']),
                'document_date' => $order->document_date->toDateString(),
                'from_quote' => $order->parent?->only(['id', 'document_number']),
                'invoice' => $order->children->firstWhere('document_type', 'invoice')?->only(['id', 'document_number']),
                'subtotal_excl' => (float) $order->subtotal_excl,
                'vat_amount' => (float) $order->vat_amount,
                'total_incl' => (float) $order->total_incl,
                'is_fulfillable' => $order->status === 'confirmed',
                'lines' => $order->lines->map(fn ($l) => [
                    'part_id' => $l->part_id,
                    'part_number' => $l->part?->part_number,
                    'description' => $l->description,
                    'qty' => (float) $l->qty,
                    'unit_price' => (float) $l->unit_price,
                    'discount_pct' => (float) $l->discount_pct,
                    'line_total_incl' => (float) $l->line_total_incl,
                ]),
            ],
        ]);
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
