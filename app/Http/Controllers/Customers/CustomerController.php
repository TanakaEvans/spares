<?php

namespace App\Http\Controllers\Customers;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\CustomerGroup;
use App\Models\CustomerReceipt;
use App\Models\PriceList;
use App\Models\SalesDocument;
use App\Models\SalesPayment;
use App\Services\ArReceiptService;
use App\Services\DocumentPdfService;
use App\Services\NumberSequenceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class CustomerController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('Customers/Index', [
            'customers' => Customer::query()
                ->with('group:id,name')
                ->when($request->string('type')->toString(), fn ($q, $t) => $q->where('type', $t))
                ->when($request->boolean('on_hold'), fn ($q) => $q->where('on_hold', true))
                ->when($request->string('search')->toString(), fn ($q, $s) => $q->where(
                    fn ($x) => $x->where('name', 'like', "%{$s}%")
                        ->orWhere('customer_number', 'like', "%{$s}%")
                        ->orWhere('phone', 'like', "%{$s}%")
                ))
                ->orderBy('name')
                ->paginate(25)
                ->withQueryString()
                ->through(fn (Customer $c) => [
                    'id' => $c->id,
                    'customer_number' => $c->customer_number,
                    'name' => $c->name,
                    'type' => $c->type,
                    'group' => $c->group?->name,
                    'phone' => $c->phone,
                    'credit_limit' => (float) $c->credit_limit,
                    'on_hold' => $c->on_hold,
                    'is_walk_in' => $c->is_walk_in,
                ]),
            'filters' => $request->only('search', 'type', 'on_hold'),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Customers/Create', $this->formOptions());
    }

    public function store(Request $request, NumberSequenceService $sequences): RedirectResponse
    {
        $data = $this->validateCustomer($request);
        $data['customer_number'] = $sequences->next('customer');

        $customer = Customer::create($data);

        return redirect()->route('customers.show', $customer)
            ->with('success', "Customer {$customer->customer_number} — {$customer->name} created.");
    }

    public function show(Customer $customer): Response
    {
        $customer->load('group:id,name,price_list_id', 'priceList:id,name');

        $documents = SalesDocument::where('customer_id', $customer->id)
            ->whereIn('document_type', ['invoice', 'credit_note'])
            ->where('status', 'posted')
            ->latest('document_date')->latest('id')
            ->limit(50)
            ->get(['id', 'document_number', 'document_type', 'document_date', 'total_incl', 'credit_mode']);

        return Inertia::render('Customers/Show', [
            'customer' => [
                'id' => $customer->id,
                'customer_number' => $customer->customer_number,
                'name' => $customer->name,
                'trading_name' => $customer->trading_name,
                'type' => $customer->type,
                'vat_number' => $customer->vat_number,
                'email' => $customer->email,
                'phone' => $customer->phone,
                'address' => $customer->address,
                'city' => $customer->city,
                'group' => $customer->group?->name,
                'price_list' => $customer->priceList?->name,
                'effective_price_list' => $customer->effectivePriceList()?->name,
                'payment_terms_days' => $customer->payment_terms_days,
                'credit_limit' => (float) $customer->credit_limit,
                'on_hold' => $customer->on_hold,
                'hold_reason' => $customer->hold_reason,
                'is_walk_in' => $customer->is_walk_in,
                'is_active' => $customer->is_active,
                'notes' => $customer->notes,
                'ar_balance' => $customer->arBalance(),
                'available_credit' => round((float) $customer->credit_limit - $customer->arBalance(), 2),
            ],
            'documents' => $documents->map(fn (SalesDocument $d) => [
                'id' => $d->id,
                'document_number' => $d->document_number,
                'document_type' => $d->document_type,
                'document_date' => $d->document_date->toDateString(),
                'total_incl' => (float) $d->total_incl,
                'credit_mode' => $d->credit_mode,
            ]),
        ]);
    }

    public function edit(Customer $customer): Response
    {
        return Inertia::render('Customers/Edit', array_merge($this->formOptions(), [
            'customer' => $customer->only([
                'id', 'customer_number', 'type', 'name', 'trading_name', 'vat_number',
                'email', 'phone', 'address', 'city', 'customer_group_id', 'price_list_id',
                'payment_terms_days', 'credit_limit', 'is_active', 'notes',
            ]),
        ]));
    }

    public function update(Request $request, Customer $customer): RedirectResponse
    {
        $customer->update($this->validateCustomer($request));

        return redirect()->route('customers.show', $customer)
            ->with('success', "Customer {$customer->name} updated.");
    }

    /** Place on / release from credit hold (audited state change). */
    public function toggleHold(Request $request, Customer $customer): RedirectResponse
    {
        $data = $request->validate([
            'on_hold' => ['required', 'boolean'],
            'hold_reason' => ['nullable', 'string', 'max:255'],
        ]);

        if ($customer->is_walk_in) {
            return back()->withErrors(['on_hold' => 'The walk-in Cash Customer cannot be held.']);
        }

        $customer->update([
            'on_hold' => $data['on_hold'],
            'hold_reason' => $data['on_hold'] ? ($data['hold_reason'] ?? 'Manual hold') : null,
        ]);

        return back()->with('success', $data['on_hold']
            ? "{$customer->name} placed on credit hold."
            : "{$customer->name} released from hold.");
    }

    /** Account statement PDF (Module 5.5): on-account activity + running balance + ageing. */
    public function statement(Customer $customer, DocumentPdfService $pdf, ArReceiptService $ar): HttpResponse
    {
        // On-account charges (debits).
        $charges = SalesPayment::where('method', 'account')
            ->whereHas('document', fn ($q) => $q->where('customer_id', $customer->id)
                ->where('document_type', 'invoice')->where('status', 'posted'))
            ->with('document:id,document_number,document_date')
            ->get()
            ->map(fn (SalesPayment $p) => [
                'date' => $p->document->document_date,
                'ref' => $p->document->document_number,
                'detail' => 'Invoice',
                'debit' => (float) $p->amount,
                'credit' => 0.0,
            ]);

        // Account-mode credit notes (credits).
        $credits = SalesDocument::where('customer_id', $customer->id)
            ->where('document_type', 'credit_note')->where('status', 'posted')
            ->where('credit_mode', 'account')->get()
            ->map(fn (SalesDocument $d) => [
                'date' => $d->document_date, 'ref' => $d->document_number,
                'detail' => 'Credit note', 'debit' => 0.0, 'credit' => (float) $d->total_incl,
            ]);

        // Receipts (credits).
        $receipts = CustomerReceipt::where('customer_id', $customer->id)->get()
            ->map(fn (CustomerReceipt $r) => [
                'date' => $r->receipt_date, 'ref' => $r->receipt_number,
                'detail' => 'Receipt ('.$r->method.')', 'debit' => 0.0, 'credit' => (float) $r->amount,
            ]);

        $rows = $charges->concat($credits)->concat($receipts)
            ->sortBy(fn ($r) => $r['date']->timestamp)->values();

        $balance = 0.0;
        $lines = $rows->map(function ($r) use (&$balance) {
            $balance = round($balance + $r['debit'] - $r['credit'], 2);

            return array_merge($r, ['date' => $r['date']->format('d M Y'), 'balance' => $balance]);
        });

        $ageing = collect($ar->ageing(now()->toDateString())['rows'])->firstWhere('customer_id', $customer->id)
            ?? ['current' => 0, 'b30' => 0, 'b60' => 0, 'b90' => 0, 'total' => $customer->arBalance()];

        return $pdf->render('print.customer-statement', [
            'customer' => $customer,
            'lines' => $lines,
            'closing' => round($balance, 2),
            'ageing' => $ageing,
        ])->stream("statement-{$customer->customer_number}.pdf");
    }

    private function formOptions(): array
    {
        return [
            'groups' => CustomerGroup::orderBy('name')->get(['id', 'name']),
            'priceLists' => PriceList::where('is_active', true)->orderBy('name')->get(['id', 'name', 'is_default']),
            'types' => ['individual', 'business', 'fleet', 'dealer'],
        ];
    }

    private function validateCustomer(Request $request): array
    {
        return $request->validate([
            'type' => ['required', 'in:individual,business,fleet,dealer'],
            'name' => ['required', 'string', 'max:255'],
            'trading_name' => ['nullable', 'string', 'max:255'],
            'vat_number' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:1000'],
            'city' => ['nullable', 'string', 'max:100'],
            'customer_group_id' => ['nullable', 'exists:customer_groups,id'],
            'price_list_id' => ['nullable', 'exists:price_lists,id'],
            'payment_terms_days' => ['required', 'integer', 'min:0', 'max:365'],
            'credit_limit' => ['required', 'numeric', 'min:0'],
            'is_active' => ['boolean'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
    }
}
