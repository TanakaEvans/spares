<?php

namespace App\Http\Controllers\VehicleRef;

use App\Http\Controllers\Controller;
use App\Models\Part;
use App\Models\PartFitment;
use App\Models\VehicleMake;
use App\Models\VehicleModel;
use App\Models\VehicleVariant;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FitmentLookupController extends Controller
{
    /**
     * "What fits this car?" — the counter's killer feature (Module 8.4).
     * Cascading make → model → variant (+ year); results grouped by
     * category with live stock availability.
     */
    public function __invoke(Request $request): Response
    {
        $makeId = $request->integer('make_id') ?: null;
        $modelId = $request->integer('model_id') ?: null;
        $variantId = $request->integer('variant_id') ?: null;
        $year = $request->integer('year') ?: null;

        $results = null;

        if ($makeId) {
            $fitments = PartFitment::forVehicle($makeId, $modelId, $variantId, $year)
                ->with('make:id,name', 'model:id,name')
                ->get();
            $partIds = $fitments->pluck('part_id')->unique();
            // Which model(s) each part's matching fitment names — null model = whole make.
            $fitsByPart = $fitments->groupBy('part_id')->map(fn ($g) => $g
                ->map(fn (PartFitment $f) => [
                    'model_id' => $f->model_id,
                    'label' => trim(($f->make?->name ?? '').' '.($f->model?->name ?? 'all models')),
                    'years' => trim(($f->year_from ?? '').'–'.($f->year_to ?? ''), '–') ?: null,
                    'engine_code' => $f->engine_code,
                ])
                ->unique(fn ($f) => $f['model_id'].'|'.$f['years'].'|'.$f['engine_code'])
                ->values());

            $parts = Part::with(['category:id,name', 'brand:id,name', 'stockLevels'])
                ->whereIn('id', $partIds)
                ->where('is_active', true)
                ->orderBy('description')
                ->get();

            $results = $parts
                ->groupBy(fn (Part $p) => $p->category?->name ?? 'Uncategorised')
                ->sortKeys()
                ->map(fn ($group) => $group->map(fn (Part $p) => [
                    'id' => $p->id,
                    'part_number' => $p->part_number,
                    'description' => $p->description,
                    'brand_id' => $p->brand_id,
                    'brand' => $p->brand?->name,
                    'is_oem' => $p->is_oem,
                    'fits' => $fitsByPart->get($p->id, collect())->values(),
                    'qty_available' => (float) $p->stockLevels->sum(
                        fn ($l) => (float) $l->qty_on_hand - (float) $l->qty_reserved
                    ),
                ])->values())
                ->toArray();
        }

        return Inertia::render('VehicleRef/Fitment/Index', [
            'makes' => VehicleMake::active()->orderBy('sort_order')->orderBy('name')->get(['id', 'name']),
            'models' => $makeId
                ? VehicleModel::where('make_id', $makeId)->orderBy('name')->get(['id', 'name', 'year_from', 'year_to'])
                : [],
            'variants' => $modelId
                ? VehicleVariant::where('model_id', $modelId)->orderBy('name')
                    ->get(['id', 'name', 'engine_code', 'year_from', 'year_to'])
                : [],
            'selection' => [
                'make_id' => $makeId,
                'model_id' => $modelId,
                'variant_id' => $variantId,
                'year' => $year,
            ],
            'results' => $results,
        ]);
    }
}
