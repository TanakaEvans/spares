<?php

namespace App\Http\Controllers\Suppliers;

use App\Http\Controllers\Controller;
use App\Models\Currency;
use App\Models\Supplier;
use App\Services\NumberSequenceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SupplierController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('Suppliers/Index', [
            'suppliers' => Supplier::withCount('purchaseOrders')
                ->when($request->string('search')->toString(), fn ($q, $s) => $q->where(
                    fn ($x) => $x->where('name', 'like', "%{$s}%")
                        ->orWhere('supplier_number', 'like', "%{$s}%")
                ))
                ->orderBy('name')
                ->paginate(25)
                ->withQueryString()
                ->through(fn (Supplier $s) => [
                    'id' => $s->id,
                    'supplier_number' => $s->supplier_number,
                    'name' => $s->name,
                    'type' => $s->type,
                    'phone' => $s->phone,
                    'lead_time_days' => $s->lead_time_days,
                    'is_active' => $s->is_active,
                    'purchase_orders_count' => $s->purchase_orders_count,
                    'ap_balance' => $s->apBalance(),
                ]),
            'filters' => $request->only('search'),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Suppliers/Create', [
            'currencies' => Currency::active()->get(['id', 'code', 'name']),
        ]);
    }

    public function store(Request $request, NumberSequenceService $sequences): RedirectResponse
    {
        $data = $this->validated($request);
        $data['supplier_number'] = $sequences->next('supplier');

        $supplier = Supplier::create($data);

        return redirect()->route('suppliers.show', $supplier)
            ->with('success', "Supplier {$supplier->name} created ({$supplier->supplier_number}).");
    }

    public function show(Supplier $supplier): Response
    {
        $supplier->load(['contacts', 'currency:id,code',
            'priceLists' => fn ($q) => $q->latest()->withCount('items'),
            'invoices' => fn ($q) => $q->latest()->limit(15),
            'purchaseOrders' => fn ($q) => $q->latest()->limit(15),
        ]);

        return Inertia::render('Suppliers/Show', [
            'supplier' => [
                'id' => $supplier->id,
                'supplier_number' => $supplier->supplier_number,
                'name' => $supplier->name,
                'trading_name' => $supplier->trading_name,
                'type' => $supplier->type,
                'vat_number' => $supplier->vat_number,
                'email' => $supplier->email,
                'phone' => $supplier->phone,
                'city' => $supplier->city,
                'country' => $supplier->country,
                'currency' => $supplier->currency?->code,
                'payment_terms_days' => $supplier->payment_terms_days,
                'lead_time_days' => $supplier->lead_time_days,
                'is_active' => $supplier->is_active,
                'notes' => $supplier->notes,
                'ap_balance' => $supplier->apBalance(),
                'contacts' => $supplier->contacts,
                'price_lists' => $supplier->priceLists->map(fn ($pl) => [
                    'id' => $pl->id, 'name' => $pl->name, 'status' => $pl->status,
                    'items_count' => $pl->items_count, 'matched_count' => $pl->matched_count,
                    'unmatched_count' => $pl->unmatched_count,
                    'effective_date' => $pl->effective_date?->toDateString(),
                ]),
                'purchase_orders' => $supplier->purchaseOrders->map(fn ($po) => [
                    'id' => $po->id, 'po_number' => $po->po_number, 'status' => $po->status,
                    'order_date' => $po->order_date->toDateString(), 'total' => (float) $po->total,
                ]),
                'invoices' => $supplier->invoices->map(fn ($inv) => [
                    'id' => $inv->id, 'supplier_ref' => $inv->supplier_ref, 'status' => $inv->status,
                    'invoice_date' => $inv->invoice_date->toDateString(),
                    'due_date' => $inv->due_date?->toDateString(), 'total' => (float) $inv->total,
                ]),
            ],
        ]);
    }

    public function edit(Supplier $supplier): Response
    {
        return Inertia::render('Suppliers/Edit', [
            'supplier' => $supplier->only([
                'id', 'supplier_number', 'name', 'trading_name', 'type', 'tax_number',
                'vat_number', 'currency_id', 'payment_terms_days', 'credit_limit',
                'lead_time_days', 'minimum_order_value', 'email', 'phone', 'address',
                'city', 'country', 'bank_name', 'bank_branch_code', 'bank_account_name',
                'bank_account_number', 'is_active', 'notes',
            ]),
            'currencies' => Currency::active()->get(['id', 'code', 'name']),
        ]);
    }

    public function update(Request $request, Supplier $supplier): RedirectResponse
    {
        $supplier->update($this->validated($request));

        return redirect()->route('suppliers.show', $supplier)
            ->with('success', "Supplier {$supplier->name} updated.");
    }

    public function storeContact(Request $request, Supplier $supplier): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'position' => ['nullable', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'is_primary' => ['sometimes', 'boolean'],
        ]);

        if ($data['is_primary'] ?? false) {
            $supplier->contacts()->update(['is_primary' => false]);
        }

        $supplier->contacts()->create($data);

        return back()->with('success', "Contact {$data['name']} added.");
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'trading_name' => ['nullable', 'string', 'max:255'],
            'type' => ['required', 'in:local,import,manufacturer,distributor,wholesaler'],
            'tax_number' => ['nullable', 'string', 'max:30'],
            'vat_number' => ['nullable', 'string', 'max:30'],
            'currency_id' => ['nullable', 'exists:currencies,id'],
            'payment_terms_days' => ['required', 'integer', 'min:0', 'max:365'],
            'credit_limit' => ['nullable', 'numeric', 'min:0'],
            'lead_time_days' => ['required', 'integer', 'min:0', 'max:365'],
            'minimum_order_value' => ['nullable', 'numeric', 'min:0'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string'],
            'city' => ['nullable', 'string', 'max:100'],
            'country' => ['nullable', 'string', 'max:100'],
            'bank_name' => ['nullable', 'string', 'max:100'],
            'bank_branch_code' => ['nullable', 'string', 'max:20'],
            'bank_account_name' => ['nullable', 'string', 'max:100'],
            'bank_account_number' => ['nullable', 'string', 'max:30'],
            'is_active' => ['sometimes', 'boolean'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        // NOT NULL columns with defaults: empty form values must not insert NULL.
        $data['credit_limit'] = $data['credit_limit'] ?? 0;
        $data['minimum_order_value'] = $data['minimum_order_value'] ?? 0;

        return $data;
    }
}
