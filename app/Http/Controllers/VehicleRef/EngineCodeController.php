<?php

namespace App\Http\Controllers\VehicleRef;

use App\Http\Controllers\Concerns\ListQuery;
use App\Http\Controllers\Concerns\RetiresRecords;
use App\Http\Controllers\Controller;
use App\Models\EngineCode;
use App\Models\Part;
use App\Models\PartFitment;
use App\Models\VehicleMake;
use App\Models\VehicleVariant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class EngineCodeController extends Controller
{
    use ListQuery, RetiresRecords;

    public function index(Request $request): Response
    {
        $modelsByCode = VehicleVariant::whereNotNull('engine_code')
            ->selectRaw('engine_code, count(distinct model_id) as n')->groupBy('engine_code')->pluck('n', 'engine_code');
        $partsByCode = PartFitment::whereNotNull('engine_code')
            ->selectRaw('engine_code, count(distinct part_id) as n')->groupBy('engine_code')->pluck('n', 'engine_code');

        return Inertia::render('VehicleRef/Engines/Index', [
            'engines' => $this->listApply(EngineCode::with('make:id,name')
                ->when($request->integer('make_id'), fn ($q, $id) => $q->where('make_id', $id))
                ->when($request->filled('fuel_type'), fn ($q) => $q->where('fuel_type', $request->string('fuel_type')->toString())),
                $request,
                [
                    'searchable' => ['code', 'description', 'make.name'],
                    'sortable' => ['code' => 'code', 'capacity_cc' => 'capacity_cc', 'fuel_type' => 'fuel_type'],
                    'defaultSort' => ['code' => 'asc'],
                ])
                ->paginate(25)
                ->withQueryString()
                ->through(fn (EngineCode $e) => $this->row($e) + [
                    // Where this engine is used — variants carrying the code, and parts fitted to it.
                    'models_count' => (int) ($modelsByCode[$e->code] ?? 0),
                    'parts_count' => (int) ($partsByCode[$e->code] ?? 0),
                ]),
            'makes' => VehicleMake::active()->orderBy('name')->get(['id', 'name']),
            'filters' => $this->listFilters($request, ['make_id', 'fuel_type']),
        ]);
    }

    /** One engine: specs, the vehicles that carry it, and every part fitted to it with stock. */
    public function show(EngineCode $engine): Response
    {
        $engine->load('make:id,name');

        $variants = VehicleVariant::with('model:id,name,make_id', 'model.make:id,name')
            ->where('engine_code', $engine->code)->orderBy('name')->get()
            ->map(fn (VehicleVariant $v) => [
                'id' => $v->id, 'name' => $v->name, 'model_id' => $v->model_id,
                'model' => trim(($v->model?->make?->name ?? '').' '.($v->model?->name ?? '')),
                'years' => trim(($v->year_from ?? '').'–'.($v->year_to ?? ''), '–') ?: null,
                'fuel_type' => $v->fuel_type, 'transmission' => $v->transmission, 'drive' => $v->drive,
            ]);

        $partIds = PartFitment::where('engine_code', $engine->code)->pluck('part_id')->unique();
        $parts = Part::with(['category:id,name', 'brand:id,name', 'stockLevels'])
            ->whereIn('id', $partIds)->orderBy('description')->limit(300)->get()
            ->map(fn (Part $p) => [
                'id' => $p->id, 'part_number' => $p->part_number, 'description' => $p->description,
                'category' => $p->category?->name, 'category_id' => $p->category_id,
                'brand' => $p->brand?->name, 'brand_id' => $p->brand_id, 'is_active' => $p->is_active,
                'qty_available' => (float) $p->stockLevels->sum(fn ($l) => (float) $l->qty_on_hand - (float) $l->qty_reserved),
            ]);

        return Inertia::render('VehicleRef/Engines/Show', [
            'engine' => $this->row($engine),
            'variants' => $variants,
            'parts' => $parts,
            'partsTotal' => $partIds->count(),
            'makes' => VehicleMake::active()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        EngineCode::create($data);

        return back()->with('success', "Engine code {$data['code']} added.");
    }

    /** Variants and fitments reference the code as text — a rename carries them with it. */
    public function update(Request $request, EngineCode $engine): RedirectResponse
    {
        $data = $this->validated($request, $engine);
        $old = $engine->code;

        DB::transaction(function () use ($engine, $data, $old) {
            $engine->update($data);
            if ($old !== $engine->code) {
                VehicleVariant::where('engine_code', $old)->update(['engine_code' => $engine->code]);
                PartFitment::where('engine_code', $old)->update(['engine_code' => $engine->code]);
            }
        });

        return redirect()->route('vehicle-ref.engines.show', $engine)
            ->with('success', $old !== $engine->code
                ? "Engine {$old} renamed to {$engine->code} — its vehicles and part fitments follow."
                : "Engine code {$engine->code} updated.");
    }

    public function destroy(EngineCode $engine): RedirectResponse
    {
        return $this->retireOrDelete($engine, "Engine code {$engine->code}", [
            'vehicle variant' => VehicleVariant::where('engine_code', $engine->code)->count(),
            'part fitment' => PartFitment::where('engine_code', $engine->code)->count(),
        ], 'vehicle-ref.engines.index');
    }

    private function row(EngineCode $e): array
    {
        return [
            'id' => $e->id, 'code' => $e->code, 'make_id' => $e->make_id,
            'make' => $e->make ? ['id' => $e->make->id, 'name' => $e->make->name] : null,
            'description' => $e->description, 'capacity_cc' => $e->capacity_cc,
            'fuel_type' => $e->fuel_type, 'aspiration' => $e->aspiration, 'cylinders' => $e->cylinders,
        ];
    }

    private function validated(Request $request, ?EngineCode $engine = null): array
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:30', Rule::unique('engine_codes', 'code')->ignore($engine)],
            'make_id' => ['nullable', 'exists:vehicle_makes,id'],
            'description' => ['nullable', 'string', 'max:255'],
            'capacity_cc' => ['nullable', 'integer', 'min:1'],
            'fuel_type' => ['nullable', 'in:petrol,diesel,hybrid,electric,lpg'],
            'aspiration' => ['nullable', 'in:naturally_aspirated,turbo,supercharged'],
            'cylinders' => ['nullable', 'integer', 'min:1', 'max:16'],
        ]);
        $data['code'] = trim($data['code']);

        return $data;
    }
}
