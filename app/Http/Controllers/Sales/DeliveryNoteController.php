<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Concerns\ListQuery;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\DeliveryNote;
use App\Services\NumberSequenceService;
use App\Support\ListExport;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class DeliveryNoteController extends Controller
{
    use ListQuery;

    private function notesQuery(Request $request): Builder
    {
        return $this->listApply(
            DeliveryNote::with('customer:id,name', 'document:id,document_number,document_type')
                ->when($request->string('status')->toString(), fn ($q, $s) => $q->where('status', $s)),
            $request,
            [
                'searchable' => ['delivery_number', 'driver', 'vehicle_reg', 'customer.name', 'document.document_number'],
                'sortable' => [
                    'delivery_number' => 'delivery_number', 'delivery_date' => 'delivery_date',
                    'driver' => 'driver', 'status' => 'status',
                ],
                'dateColumn' => 'delivery_date',
                'defaultSort' => ['id' => 'desc'],
            ]
        );
    }

    /** The filtered delivery-note list as CSV or branded PDF (same filters as the grid). */
    public function export(Request $request): HttpResponse
    {
        $rows = $this->notesQuery($request)->limit(5000)->get()->map(fn (DeliveryNote $d) => [
            $d->delivery_number, $d->customer?->name, $d->document?->document_number,
            $d->delivery_date?->toDateString(), $d->driver ?? '—', $d->vehicle_reg ?? '—',
            ucwords($d->status),
        ]);

        return ListExport::respond($request, 'delivery-notes', 'Delivery Notes',
            ['Delivery No', 'Customer', 'Document', 'Date', 'Driver', 'Vehicle', 'Status'], $rows);
    }

    public function index(Request $request): Response
    {
        return Inertia::render('Sales/DeliveryNotes/Index', [
            'notes' => $this->notesQuery($request)->paginate(20)
                ->withQueryString()
                ->through(fn (DeliveryNote $d) => [
                    'id' => $d->id, 'delivery_number' => $d->delivery_number,
                    'customer' => $d->customer?->name, 'customer_id' => $d->customer_id,
                    'document' => $d->document?->only(['id', 'document_number', 'document_type']),
                    'delivery_date' => $d->delivery_date->toDateString(), 'driver' => $d->driver, 'vehicle_reg' => $d->vehicle_reg, 'status' => $d->status,
                ]),
            'customers' => Customer::orderBy('name')->limit(200)->get(['id', 'name']),
            'branches' => Branch::where('status', 'active')->get(['id', 'name']),
            'filters' => $this->listFilters($request, ['status']),
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
