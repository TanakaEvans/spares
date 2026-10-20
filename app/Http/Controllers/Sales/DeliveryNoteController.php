<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\DeliveryNote;
use App\Services\NumberSequenceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DeliveryNoteController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Sales/DeliveryNotes/Index', [
            'notes' => DeliveryNote::with('customer:id,name', 'document:id,document_number,document_type')->latest('id')->paginate(20)
                ->through(fn (DeliveryNote $d) => [
                    'id' => $d->id, 'delivery_number' => $d->delivery_number,
                    'customer' => $d->customer?->name, 'customer_id' => $d->customer_id,
                    'document' => $d->document?->only(['id', 'document_number', 'document_type']),
                    'delivery_date' => $d->delivery_date->toDateString(), 'driver' => $d->driver, 'vehicle_reg' => $d->vehicle_reg, 'status' => $d->status,
                ]),
            'customers' => Customer::orderBy('name')->limit(200)->get(['id', 'name']),
            'branches' => Branch::where('status', 'active')->get(['id', 'name']),
        ]);
    }

    public function show(DeliveryNote $deliveryNote): Response
    {
        $deliveryNote->load('customer:id,name,customer_number', 'branch:id,name', 'document:id,document_number,document_type');

        return Inertia::render('Sales/DeliveryNotes/Show', [
            'note' => [
                'id' => $deliveryNote->id,
                'delivery_number' => $deliveryNote->delivery_number,
                'status' => $deliveryNote->status,
                'customer' => $deliveryNote->customer?->only(['id', 'name', 'customer_number']),
                'branch' => $deliveryNote->branch?->only(['id', 'name']),
                'document' => $deliveryNote->document?->only(['id', 'document_number', 'document_type']),
                'delivery_date' => $deliveryNote->delivery_date->toDateString(),
                'driver' => $deliveryNote->driver,
                'vehicle_reg' => $deliveryNote->vehicle_reg,
                'address' => $deliveryNote->address,
                'created_at' => $deliveryNote->created_at?->toDateString(),
            ],
        ]);
    }

    public function store(Request $request, NumberSequenceService $seq): RedirectResponse
    {
        $data = $request->validate([
            'customer_id' => ['required', 'exists:customers,id'], 'branch_id' => ['required', 'exists:branches,id'],
            'document_id' => ['nullable', 'exists:sales_documents,id'],
            'delivery_date' => ['required', 'date'], 'driver' => ['nullable', 'string', 'max:120'],
            'vehicle_reg' => ['nullable', 'string', 'max:20'], 'address' => ['nullable', 'string', 'max:500'],
        ]);
        DeliveryNote::create($data + ['delivery_number' => $seq->next('delivery_note', $data['branch_id']), 'status' => 'dispatched']);

        return back()->with('success', 'Delivery note created.');
    }

    public function transition(Request $request, DeliveryNote $deliveryNote): RedirectResponse
    {
        $deliveryNote->update(['status' => $request->validate(['status' => ['required', 'in:dispatched,delivered']])['status']]);

        return back()->with('success', 'Delivery updated.');
    }
}
