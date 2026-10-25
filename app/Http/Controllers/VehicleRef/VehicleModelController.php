<?php

namespace App\Http\Controllers\VehicleRef;

use App\Http\Controllers\Concerns\ListQuery;
use App\Http\Controllers\Concerns\RetiresRecords;
use App\Http\Controllers\Controller;
use App\Models\CustomerVehicle;
use App\Models\EngineCode;
use App\Models\Part;
use App\Models\PartFitment;
use App\Models\VehicleMake;
use App\Models\VehicleModel;
use App\Models\VehicleVariant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class VehicleModelController extends Controller
{
    use ListQuery, RetiresRecords;
    public function index(Request $request): Response
    {
        return Inertia::render('VehicleRef/Models/Index', [
            'models' => $this->listApply(VehicleModel::with('make:id,name')->withCount('variants')
                ->when($request->integer('make_id'), fn ($q, $id) => $q->where('make_id', $id)),
                $request,
                [
                    // Search matches the model, its make, or a variant's engine code — so
                    // make/engine EntityLinks land on the right list.
                    'searchable' => ['name', 'make.name', 'variants.engine_code'],
                    'sortable' => ['name' => 'name'],
                    'defaultSort' => ['name' => 'asc'],
                ])
                ->paginate(25)
                ->withQueryString(),
            'makes' => VehicleMake::active()->orderBy('name')->get(['id', 'name']),
            'filters' => $this->listFilters($request, ['make_id']),
        ]);
    }

    public function show(VehicleModel $model): Response
    {
        $model->load('make:id,name', 'variants');

        // Parts recorded as fitting this model (model-level or make-wide fitments).
        $partIds = PartFitment::forVehicle($model->make_id, $model->id)->pluck('part_id')->unique();
        $fittingParts = Part::with(['category:id,name', 'brand:id,name', 'stockLevels'])
            ->whereIn('id', $partIds)
            ->orderBy('description')
            ->limit(200)
            ->get()
            ->map(fn (Part $p) => [
                'id' => $p->id,
                'part_number' => $p->part_number,
                'description' => $p->description,
                'category_id' => $p->category_id,
                'category' => $p->category?->name,
                'brand_id' => $p->brand_id,
                'brand' => $p->brand?->name,
                'is_active' => $p->is_active,
                'qty_available' => (float) $p->stockLevels->sum(
                    fn ($l) => (float) $l->qty_on_hand - (float) $l->qty_reserved
                ),
            ])->values();

        return Inertia::render('VehicleRef/Models/Show', [
            'model' => $model,
            'fittingParts' => $fittingParts,
            'fittingPartsTotal' => $partIds->count(),
            'makes' => VehicleMake::active()->orderBy('name')->get(['id', 'name']),
            'engineCodes' => EngineCode::where(fn ($q) => $q->whereNull('make_id')->orWhere('make_id', $model->make_id))
                ->orderBy('code')->pluck('code'),
            // variant id → fitments naming it, so the UI can explain why a delete is blocked
            'variantUsage' => PartFitment::whereIn('variant_id', $model->variants->pluck('id'))
                ->selectRaw('variant_id, count(*) as n')->groupBy('variant_id')->pluck('n', 'variant_id'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'make_id' => ['required', 'exists:vehicle_makes,id'],
            'name' => ['required', 'string', 'max:100'],
            'body_type' => ['nullable', 'string', 'max:30'],
            'year_from' => ['nullable', 'integer', 'min:1950', 'max:2100'],
            'year_to' => ['nullable', 'integer', 'min:1950', 'max:2100', 'gte:year_from'],
        ]);

        VehicleModel::create($data);

        return back()->with('success', "Model {$data['name']} added.");
    }

    public function update(Request $request, VehicleModel $model): RedirectResponse
    {
        $data = $request->validate([
            'make_id' => ['required', 'exists:vehicle_makes,id'],
            'name' => ['required', 'string', 'max:100'],
            'body_type' => ['nullable', 'string', 'max:30'],
            'generation' => ['nullable', 'string', 'max:50'],
            'year_from' => ['nullable', 'integer', 'min:1950', 'max:2100'],
            'year_to' => ['nullable', 'integer', 'min:1950', 'max:2100', 'gte:year_from'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $model->update($data);

        return back()->with('success', "Model {$model->name} updated.");
    }

    public function destroy(VehicleModel $model): RedirectResponse
    {
        // Its own variants go with it (FK cascade); only outside references block a delete.
        return $this->retireOrDelete($model, "Model {$model->name}", [
            'part fitment' => PartFitment::where('model_id', $model->id)
                ->orWhereIn('variant_id', $model->variants()->select('id'))->count(),
            'customer vehicle' => CustomerVehicle::where('model_id', $model->id)->count(),
        ], 'vehicle-ref.models.index');
    }

    public function storeVariant(Request $request, VehicleModel $model): RedirectResponse
    {
        $data = $this->variantData($request);
        $model->variants()->create($data);

        return back()->with('success', "Variant {$data['name']} added to {$model->name}.");
    }

    public function updateVariant(Request $request, VehicleModel $model, VehicleVariant $variant): RedirectResponse
    {
        abort_unless($variant->model_id === $model->id, 404);
        $variant->update($this->variantData($request));

        return back()->with('success', "Variant {$variant->name} updated.");
    }

    /** Variants have no active flag — one still referenced by fitments or vehicles is kept. */
    public function destroyVariant(VehicleModel $model, VehicleVariant $variant): RedirectResponse
    {
        abort_unless($variant->model_id === $model->id, 404);

        return $this->retireOrDelete($variant, "Variant {$variant->name}", [
            'part fitment' => PartFitment::where('variant_id', $variant->id)->count(),
            'customer vehicle' => CustomerVehicle::where('variant_id', $variant->id)->count(),
        ]);
    }

    private function variantData(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'engine_code' => ['nullable', 'string', 'max:30'],
            'engine_size_cc' => ['nullable', 'integer', 'min:1'],
            'fuel_type' => ['nullable', 'in:petrol,diesel,hybrid,electric,lpg'],
            'power_kw' => ['nullable', 'integer', 'min:1'],
            'transmission' => ['nullable', 'in:manual,automatic,cvt,amt'],
            'drive' => ['nullable', 'in:4x2,4x4,awd,fwd,rwd'],
            'year_from' => ['nullable', 'integer', 'min:1950', 'max:2100'],
            'year_to' => ['nullable', 'integer', 'min:1950', 'max:2100', 'gte:year_from'],
        ]);
    }
}
