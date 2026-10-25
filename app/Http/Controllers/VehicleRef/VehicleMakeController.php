<?php

namespace App\Http\Controllers\VehicleRef;

use App\Http\Controllers\Concerns\ListQuery;
use App\Http\Controllers\Concerns\RetiresRecords;
use App\Http\Controllers\Controller;
use App\Models\CustomerVehicle;
use App\Models\EngineCode;
use App\Models\PartFitment;
use App\Models\TechnicalBulletin;
use App\Models\VehicleMake;
use App\Models\VehicleModel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class VehicleMakeController extends Controller
{
    use ListQuery, RetiresRecords;

    public function index(Request $request): Response
    {
        $partsByMake = PartFitment::selectRaw('make_id, count(distinct part_id) as n')->groupBy('make_id')->pluck('n', 'make_id');

        return Inertia::render('VehicleRef/Makes/Index', [
            'makes' => $this->listApply(VehicleMake::withCount('models')
                ->when($request->filled('status'), fn ($q) => $q->where('is_active', $request->string('status')->toString() === 'active')),
                $request, [
                    'searchable' => ['name', 'code', 'country_of_origin'],
                    'sortable' => ['name' => 'name', 'sort_order' => 'sort_order', 'models_count' => 'models_count'],
                    'defaultSort' => ['sort_order' => 'asc', 'name' => 'asc'],
                ])
                ->paginate(25)
                ->withQueryString()
                ->through(fn (VehicleMake $m) => $m->toArray() + ['parts_count' => (int) ($partsByMake[$m->id] ?? 0)]),
            'filters' => $this->listFilters($request, ['status']),
        ]);
    }

    /** Everything about one make: its models, engines, fitted parts and bulletins. */
    public function show(VehicleMake $make): Response
    {
        $partsByModel = PartFitment::where('make_id', $make->id)->whereNotNull('model_id')
            ->selectRaw('model_id, count(distinct part_id) as n')->groupBy('model_id')->pluck('n', 'model_id');

        return Inertia::render('VehicleRef/Makes/Show', [
            'make' => $make->only(['id', 'name', 'code', 'country_of_origin', 'sort_order', 'is_active']),
            'stats' => [
                'models' => $make->models()->count(),
                'engines' => EngineCode::where('make_id', $make->id)->count(),
                'parts' => PartFitment::where('make_id', $make->id)->distinct('part_id')->count('part_id'),
                'make_wide_parts' => PartFitment::where('make_id', $make->id)->whereNull('model_id')->distinct('part_id')->count('part_id'),
                'customer_vehicles' => CustomerVehicle::where('make_id', $make->id)->count(),
            ],
            'models' => VehicleModel::where('make_id', $make->id)->withCount('variants')->orderBy('name')->get()
                ->map(fn (VehicleModel $m) => [
                    'id' => $m->id, 'name' => $m->name, 'body_type' => $m->body_type,
                    'year_from' => $m->year_from, 'year_to' => $m->year_to, 'is_active' => $m->is_active,
                    'variants_count' => $m->variants_count, 'parts_count' => (int) ($partsByModel[$m->id] ?? 0),
                ]),
            'engines' => EngineCode::where('make_id', $make->id)->orderBy('code')
                ->get(['id', 'code', 'description', 'capacity_cc', 'fuel_type', 'aspiration']),
            'bulletins' => TechnicalBulletin::where('make_id', $make->id)->latest()->limit(10)
                ->get(['id', 'title', 'category', 'is_active', 'created_at']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        VehicleMake::create($data);

        return back()->with('success', "Make {$data['name']} added.");
    }

    public function update(Request $request, VehicleMake $make): RedirectResponse
    {
        $make->update($this->validated($request, $make));

        return back()->with('success', "Make {$make->name} updated.");
    }

    public function destroy(VehicleMake $make): RedirectResponse
    {
        return $this->retireOrDelete($make, "Make {$make->name}", [
            'model' => $make->models()->count(),
            'part fitment' => PartFitment::where('make_id', $make->id)->count(),
            'engine code' => EngineCode::where('make_id', $make->id)->count(),
            'customer vehicle' => CustomerVehicle::where('make_id', $make->id)->count(),
            'bulletin' => TechnicalBulletin::where('make_id', $make->id)->count(),
        ], 'vehicle-ref.makes.index');
    }

    private function validated(Request $request, ?VehicleMake $make = null): array
    {
        // Normalise first so uniqueness is checked on the value actually stored.
        $request->merge(['code' => strtoupper(trim((string) $request->input('code')))]);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('vehicle_makes', 'name')->ignore($make)],
            'code' => ['required', 'string', 'max:10', 'alpha_num', Rule::unique('vehicle_makes', 'code')->ignore($make)],
            'country_of_origin' => ['nullable', 'string', 'max:100'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999'],
            'is_active' => ['sometimes', 'boolean'],
        ]);
        $data['sort_order'] ??= $make?->sort_order ?? 99;

        return $data;
    }
}
