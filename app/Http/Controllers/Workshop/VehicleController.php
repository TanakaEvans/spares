<?php

namespace App\Http\Controllers\Workshop;

use App\Http\Controllers\Concerns\ListQuery;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\CustomerVehicle;
use App\Models\VehicleMake;
use App\Models\VehicleServiceHistory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class VehicleController extends Controller
{
    use ListQuery;

    public function index(Request $request): Response
    {
        return Inertia::render('Workshop/Vehicles/Index', [
            'vehicles' => $this->listApply(
                CustomerVehicle::with('customer:id,name', 'make:id,name', 'model:id,name'),
                $request,
                [
                    'searchable' => ['registration', 'vin', 'customer.name'],
                    'sortable' => ['registration' => 'registration', 'year' => 'year', 'created_at' => 'created_at'],
                    'defaultSort' => ['registration' => 'asc'],
                ]
            )->paginate(20)
                ->withQueryString()
                ->through(fn (CustomerVehicle $v) => [
                    'id' => $v->id,
                    'registration' => $v->registration,
                    'make_model' => trim(($v->make?->name ?? '').' '.($v->model?->name ?? '')) ?: '—',
                    'model_id' => $v->model_id,
                    'year' => $v->year,
                    'customer' => $v->customer?->name,
                    'customer_id' => $v->customer_id,
                ]),
            'filters' => $this->listFilters($request),
        ]);
    }

    public function create(Request $request): Response
    {
        return Inertia::render('Workshop/Vehicles/Create', [
            'makes' => VehicleMake::orderBy('name')->get(['id', 'name']),
            'preselectedCustomerId' => $request->integer('customer_id') ?: null,
        ]);
    }

    public function customerLookup(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));
        if (mb_strlen($q) < 2) {
            return response()->json(['customers' => []]);
        }

        return response()->json([
            'customers' => Customer::where('is_walk_in', false)->active()
                ->where(fn ($x) => $x->where('name', 'like', "%{$q}%")->orWhere('customer_number', 'like', "%{$q}%"))
                ->orderBy('name')->limit(8)->get(['id', 'name', 'customer_number'])
                ->map(fn ($c) => ['id' => $c->id, 'name' => $c->name, 'customer_number' => $c->customer_number]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'customer_id' => ['required', 'exists:customers,id'],
            'registration' => ['required', 'string', 'max:20'],
            'vin' => ['nullable', 'string', 'size:17'],
            'make_id' => ['nullable', 'exists:vehicle_makes,id'],
            'model_id' => ['nullable', 'exists:vehicle_models,id'],
            'variant_id' => ['nullable', 'exists:vehicle_variants,id'],
            'year' => ['nullable', 'integer', 'min:1950', 'max:'.(date('Y') + 1)],
            'engine_code' => ['nullable', 'string', 'max:30'],
            'colour' => ['nullable', 'string', 'max:40'],
        ], [
            'vin.size' => 'A VIN is exactly 17 characters.',
        ]);

        // One active vehicle per registration.
        $exists = CustomerVehicle::where('registration', $data['registration'])->where('is_active', true)->exists();
        if ($exists) {
            return back()->withErrors(['registration' => "A vehicle with registration {$data['registration']} already exists."]);
        }

        $vehicle = CustomerVehicle::create($data + ['is_active' => true]);

        return redirect()->route('workshop.vehicles.show', $vehicle)
            ->with('success', "Vehicle {$vehicle->registration} registered.");
    }

    public function show(CustomerVehicle $vehicle): Response
    {
        $vehicle->load('customer:id,name,customer_number', 'make:id,name', 'model:id,name', 'variant:id,name');

        $history = VehicleServiceHistory::where('vehicle_id', $vehicle->id)
            ->with('jobCard:id,job_number,status,technician_id,sales_document_id', 'jobCard.technician:id,name', 'jobCard.invoice:id,document_number')
            ->orderByDesc('service_date')->get()
            ->map(fn (VehicleServiceHistory $h) => [
                'id' => $h->id,
                'service_date' => $h->service_date->toDateString(),
                'odometer' => $h->odometer,
                'summary' => $h->summary,
                'job_number' => $h->jobCard?->job_number,
                'job_card_id' => $h->job_card_id,
                'job_status' => $h->jobCard?->status,
                'technician' => $h->jobCard?->technician?->name,
                'technician_id' => $h->jobCard?->technician_id,
                'invoice_number' => $h->jobCard?->invoice?->document_number,
                'invoice_id' => $h->jobCard?->sales_document_id,
            ]);

        return Inertia::render('Workshop/Vehicles/Show', [
            'vehicle' => [
                'id' => $vehicle->id,
                'registration' => $vehicle->registration,
                'vin' => $vehicle->vin,
                'make' => $vehicle->make?->name,
                'make_id' => $vehicle->make_id,
                'model' => $vehicle->model?->name,
                'model_id' => $vehicle->model_id,
                'variant' => $vehicle->variant?->name,
                'year' => $vehicle->year,
                'engine_code' => $vehicle->engine_code,
                'colour' => $vehicle->colour,
                'customer' => $vehicle->customer?->only(['id', 'name', 'customer_number']),
            ],
            'history' => $history,
        ]);
    }
}
