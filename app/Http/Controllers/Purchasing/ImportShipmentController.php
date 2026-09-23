<?php

namespace App\Http\Controllers\Purchasing;

use App\Http\Controllers\Controller;
use App\Models\ImportShipment;
use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ImportShipmentController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Purchasing/Imports/Index', [
            'shipments' => ImportShipment::with('supplier:id,name')->latest('id')->paginate(20)
                ->through(fn (ImportShipment $s) => [
                    'id' => $s->id, 'shipment_ref' => $s->shipment_ref, 'supplier' => $s->supplier?->name,
                    'origin_country' => $s->origin_country, 'status' => $s->status, 'eta' => $s->eta?->toDateString(),
                    'goods_value' => (float) $s->goods_value, 'freight' => (float) $s->freight, 'duty' => (float) $s->duty, 'landed' => $s->landedTotal(),
                ]),
            'suppliers' => Supplier::active()->orderBy('name')->get(['id', 'name']),
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

    public function transition(Request $request, ImportShipment $shipment): RedirectResponse
    {
        $shipment->update(['status' => $request->validate(['status' => ['required', 'in:ordered,in_transit,customs,cleared,received']])['status']]);

        return back()->with('success', 'Shipment updated.');
    }
}
