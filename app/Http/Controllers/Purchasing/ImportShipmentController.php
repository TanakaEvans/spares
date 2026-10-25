<?php

namespace App\Http\Controllers\Purchasing;

use App\Http\Controllers\Concerns\ListQuery;
use App\Http\Controllers\Controller;
use App\Models\ImportShipment;
use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ImportShipmentController extends Controller
{
    use ListQuery;

    public function index(Request $request): Response
    {
        return Inertia::render('Purchasing/Imports/Index', [
            'shipments' => $this->listApply(
                ImportShipment::with('supplier:id,name')
                    ->when($request->string('status')->toString(), fn ($q, $s) => $q->where('status', $s)),
                $request,
                [
                    'searchable' => ['shipment_ref', 'origin_country', 'supplier.name'],
                    'sortable' => [
                        'shipment_ref' => 'shipment_ref', 'status' => 'status',
                        'eta' => 'eta', 'goods_value' => 'goods_value',
                    ],
                    'dateColumn' => 'eta',
                    'defaultSort' => ['id' => 'desc'],
                ]
            )->paginate(20)->withQueryString()
                ->through(fn (ImportShipment $s) => [
                    'id' => $s->id, 'shipment_ref' => $s->shipment_ref,
                    'supplier_id' => $s->supplier_id, 'supplier' => $s->supplier?->name,
                    'origin_country' => $s->origin_country, 'status' => $s->status, 'eta' => $s->eta?->toDateString(),
                    'goods_value' => (float) $s->goods_value, 'freight' => (float) $s->freight, 'duty' => (float) $s->duty, 'landed' => $s->landedTotal(),
                ]),
            'suppliers' => Supplier::active()->orderBy('name')->get(['id', 'name']),
            'stats' => [
                'active' => (int) ImportShipment::whereNot('status', 'received')->count(),
                'in_transit_value' => round((float) ImportShipment::whereNot('status', 'received')
                    ->get()->sum(fn ($s) => $s->landedTotal()), 2),
            ],
            'filters' => $this->listFilters($request, ['status']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'shipment_ref' => ['required', 'string', 'max:40', 'unique:import_shipments,shipment_ref'],
            'supplier_id' => ['nullable', 'exists:suppliers,id'], 'origin_country' => ['nullable', 'string', 'max:60'],
            'eta' => ['nullable', 'date'], 'goods_value' => ['nullable', 'numeric', 'min:0'],
            'freight' => ['nullable', 'numeric', 'min:0'], 'duty' => ['nullable', 'numeric', 'min:0'], 'notes' => ['nullable', 'string', 'max:1000'],
        ]);
        ImportShipment::create($data + ['status' => 'ordered']);

        return back()->with('success', 'Import shipment created.');
    }

    public function show(ImportShipment $shipment): Response
    {
        $shipment->load('supplier:id,name,country');

        return Inertia::render('Purchasing/Imports/Show', [
            'shipment' => [
                'id' => $shipment->id,
                'shipment_ref' => $shipment->shipment_ref,
                'status' => $shipment->status,
                'origin_country' => $shipment->origin_country,
                'eta' => $shipment->eta?->toDateString(),
                'goods_value' => (float) $shipment->goods_value,
                'freight' => (float) $shipment->freight,
                'duty' => (float) $shipment->duty,
                'landed' => $shipment->landedTotal(),
                'notes' => $shipment->notes,
                'created_at' => $shipment->created_at?->toDateString(),
                'supplier_id' => $shipment->supplier_id,
                'supplier' => $shipment->supplier?->name,
            ],
        ]);
    }

    public function transition(Request $request, ImportShipment $shipment): RedirectResponse
    {
        $shipment->update(['status' => $request->validate(['status' => ['required', 'in:ordered,in_transit,customs,cleared,received']])['status']]);

        return back()->with('success', 'Shipment updated.');
    }
}
