<?php

namespace App\Http\Controllers\VehicleRef;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Part;
use App\Models\PartFitment;
use App\Models\VehicleMake;
use App\Models\VehicleModel;
use App\Models\VehicleVariant;
use App\Services\PosTerminalService;
use App\Services\SettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FitmentLookupController extends Controller
{
    /**
     * "What fits this car?" — the counter's killer feature (Module 8.4).
     * Cascading make → model → variant (+ year), or the type-a-vehicle box.
     * Each part is presented exactly as the till sees it (PosTerminalService):
     * selling price, branch stock, bin and supersession.
     */
    public function __invoke(Request $request, PosTerminalService $till, SettingsService $settings): Response
    {
        $makeId = $request->integer('make_id') ?: null;
        $modelId = $request->integer('model_id') ?: null;
        $variantId = $request->integer('variant_id') ?: null;
        $year = $request->integer('year') ?: null;

        $results = null;
        $branch = Branch::where('is_main_branch', true)->first() ?? Branch::first();

        if ($makeId) {
            $fitments = PartFitment::forVehicle($makeId, $modelId, $variantId, $year)
                ->with('make:id,name', 'model:id,name')
                ->get();
            // Which model(s) each part's matching fitment names — null model = whole make.
            $fitsByPart = $fitments->groupBy('part_id')->map(fn ($g) => $g
                ->map(fn (PartFitment $f) => [
                    'model_id' => $f->model_id,
                    'label' => trim(($f->make?->name ?? '').' '.($f->model?->name ?? 'all models')),
                    'years' => trim(($f->year_from ?? '').'–'.($f->year_to ?? ''), '–') ?: null,
                    'engine_code' => $f->engine_code,
                    'confirmed' => (bool) $f->confirmed,
                ])
                ->unique(fn ($f) => $f['model_id'].'|'.$f['years'].'|'.$f['engine_code'])
                ->values());

            $parts = Part::with(['category:id,name', 'brand:id,name', 'stockLevels:id,part_id,qty_on_hand,qty_reserved', 'supersededBy.newPart:id,part_number'])
                ->whereIn('id', $fitments->pluck('part_id')->unique())
                ->where('is_active', true)
                ->orderBy('description')
                ->limit(1500)
                ->get();

            $byId = $parts->keyBy('id');
            // No branch yet (fresh install): still list what fits, just without till pricing/stock.
            $rows = $branch
                ? $till->present($parts, $branch, null)
                : $parts->map(fn (Part $p) => [
                    'id' => $p->id, 'part_number' => $p->part_number, 'description' => $p->description,
                    'oem_number' => $p->oem_number, 'category' => $p->category?->name, 'price' => 0.0, 'priced' => false,
                    'on_hand' => 0.0, 'available' => 0.0, 'reorder_point' => 0.0, 'bin' => null,
                    'superseded_by' => $p->supersededBy?->newPart?->part_number, 'discontinued' => (bool) $p->is_discontinued,
                ]);

            $results = $rows->map(function (array $row) use ($byId, $fitsByPart) {
                $p = $byId[$row['id']];

                return $row + [
                    'category_id' => $p->category_id,
                    'brand_id' => $p->brand_id,
                    'brand' => $p->brand?->name,
                    'is_oem' => (bool) $p->is_oem,
                    'all_branches' => round((float) $p->stockLevels->sum(fn ($l) => (float) $l->qty_on_hand - (float) $l->qty_reserved), 2),
                    'fits' => $fitsByPart->get($p->id, collect())->values(),
                ];
            })->values();
        }

        return Inertia::render('VehicleRef/Fitment/Index', [
            'makes' => VehicleMake::active()->orderBy('sort_order')->orderBy('name')->get(['id', 'name']),
            'models' => $makeId
                ? VehicleModel::where('make_id', $makeId)->where('is_active', true)->orderBy('name')->get(['id', 'name', 'year_from', 'year_to'])
                : [],
            'variants' => $modelId
                ? VehicleVariant::where('model_id', $modelId)->orderBy('name')->get(['id', 'name', 'engine_code', 'year_from', 'year_to'])
                : [],
            'selection' => ['make_id' => $makeId, 'model_id' => $modelId, 'variant_id' => $variantId, 'year' => $year],
            'vehicleLabel' => $this->label($makeId, $modelId, $variantId, $year),
            'results' => $results,
            'branch' => $branch?->only(['id', 'name']),
            'vatRate' => (float) $settings->get('tax.vat_rate_default'),
        ]);
    }

    /** Type-a-vehicle: "hilux 2.8", "corolla 2010", "1gd" → make/model/variant picks. */
    public function vehicles(Request $request): JsonResponse
    {
        $words = array_slice(preg_split('/\s+/', trim((string) $request->query('q', '')), -1, PREG_SPLIT_NO_EMPTY), 0, 4);
        if ($words === [] || mb_strlen(implode('', $words)) < 2) {
            return response()->json(['vehicles' => []]);
        }

        $year = collect($words)->first(fn ($w) => preg_match('/^(19|20)\d{2}$/', $w));
        $terms = array_values(array_filter($words, fn ($w) => $w !== $year));
        $out = collect();

        // Variants (most specific) — every word must hit make, model, variant or engine code.
        $variants = VehicleVariant::with('model:id,name,make_id', 'model.make:id,name')
            ->whereHas('model', fn ($q) => $q->where('is_active', true));
        foreach ($terms as $t) {
            $like = '%'.str_replace(['%', '_'], ['\%', '\_'], $t).'%';
            $variants->where(fn ($q) => $q->where('name', 'like', $like)->orWhere('engine_code', 'like', $like)
                ->orWhereHas('model', fn ($m) => $m->where('name', 'like', $like)->orWhereHas('make', fn ($mk) => $mk->where('name', 'like', $like))));
        }
        foreach ($variants->limit(8)->get() as $v) {
            $out->push([
                'make_id' => $v->model->make_id, 'model_id' => $v->model_id, 'variant_id' => $v->id, 'year' => $year,
                'label' => trim("{$v->model->make?->name} {$v->model->name} {$v->name}"),
                'hint' => trim(($v->engine_code ?? '').' '.trim(($v->year_from ?? '').'–'.($v->year_to ?? ''), '–')),
            ]);
        }

        // Models, then makes, for anything the variants didn't cover.
        $models = VehicleModel::with('make:id,name')->where('is_active', true);
        foreach ($terms as $t) {
            $like = '%'.str_replace(['%', '_'], ['\%', '\_'], $t).'%';
            $models->where(fn ($q) => $q->where('name', 'like', $like)->orWhereHas('make', fn ($mk) => $mk->where('name', 'like', $like)));
        }
        foreach ($models->limit(6)->get() as $m) {
            $out->push([
                'make_id' => $m->make_id, 'model_id' => $m->id, 'variant_id' => null, 'year' => $year,
                'label' => trim("{$m->make?->name} {$m->name}"), 'hint' => 'all variants',
            ]);
        }
        if (count($terms) === 1) {
            foreach (VehicleMake::active()->where('name', 'like', '%'.$terms[0].'%')->limit(3)->get() as $mk) {
                $out->push(['make_id' => $mk->id, 'model_id' => null, 'variant_id' => null, 'year' => $year, 'label' => $mk->name, 'hint' => 'all models']);
            }
        }

        return response()->json([
            'vehicles' => $out->unique(fn ($v) => $v['make_id'].'|'.$v['model_id'].'|'.$v['variant_id'])->take(10)->values(),
        ]);
    }

    private function label(?int $makeId, ?int $modelId, ?int $variantId, ?int $year): ?string
    {
        if (! $makeId) {
            return null;
        }
        $parts = [
            VehicleMake::whereKey($makeId)->value('name'),
            $modelId ? VehicleModel::whereKey($modelId)->value('name') : null,
            $variantId ? VehicleVariant::whereKey($variantId)->value('name') : null,
            $year ? (string) $year : null,
        ];

        return implode(' ', array_filter($parts));
    }
}
