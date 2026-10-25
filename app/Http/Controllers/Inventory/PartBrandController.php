<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Concerns\ListQuery;
use App\Http\Controllers\Concerns\RetiresRecords;
use App\Http\Controllers\Controller;
use App\Models\Part;
use App\Models\PartBrand;
use App\Models\PartCrossReference;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class PartBrandController extends Controller
{
    use ListQuery, RetiresRecords;

    public function index(Request $request): Response
    {
        return Inertia::render('Inventory/Brands/Index', [
            'brands' => $this->listApply(
                PartBrand::withCount('parts')
                    ->when($request->filled('status'), fn ($q) => $q->where('is_active', $request->string('status')->toString() === 'active'))
                    ->when($request->filled('kind'), fn ($q) => $q->where('is_oem_brand', $request->string('kind')->toString() === 'oem')),
                $request,
                [
                    'searchable' => ['name', 'code', 'country_of_origin'],
                    'sortable' => ['name' => 'name', 'is_active' => 'is_active', 'parts_count' => 'parts_count'],
                    'defaultSort' => ['name' => 'asc'],
                ]
            )
                ->paginate(25)
                ->withQueryString(),
            'filters' => $this->listFilters($request, ['status', 'kind']),
        ]);
    }

    /** One brand: its parts (with live stock) and a category breakdown. */
    public function show(PartBrand $brand): Response
    {
        $parts = Part::with(['category:id,name', 'stockLevels:id,part_id,qty_on_hand,qty_reserved'])
            ->where('brand_id', $brand->id)->orderBy('part_number')->limit(500)->get()
            ->map(fn (Part $p) => [
                'id' => $p->id, 'part_number' => $p->part_number, 'description' => $p->description,
                'category' => $p->category?->name, 'category_id' => $p->category_id, 'is_active' => $p->is_active,
                'qty_available' => round((float) $p->stockLevels->sum(fn ($l) => (float) $l->qty_on_hand - (float) $l->qty_reserved), 2),
            ]);

        return Inertia::render('Inventory/Brands/Show', [
            'brand' => $brand->only(['id', 'name', 'code', 'country_of_origin', 'is_oem_brand', 'is_active']),
            'parts' => $parts,
            'partsTotal' => Part::where('brand_id', $brand->id)->count(),
            'crossRefs' => PartCrossReference::where('brand_id', $brand->id)->count(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        PartBrand::create($data);

        return back()->with('success', "Brand {$data['name']} added.");
    }

    public function update(Request $request, PartBrand $brand): RedirectResponse
    {
        $brand->update($this->validated($request, $brand));

        return back()->with('success', "Brand {$brand->name} updated.");
    }

    public function destroy(PartBrand $brand): RedirectResponse
    {
        return $this->retireOrDelete($brand, "Brand {$brand->name}", [
            'part' => Part::withTrashed()->where('brand_id', $brand->id)->count(),
            'cross-reference' => PartCrossReference::where('brand_id', $brand->id)->count(),
        ], 'inventory.brands.index');
    }

    private function validated(Request $request, ?PartBrand $brand = null): array
    {
        $request->merge(['code' => strtoupper(trim((string) $request->input('code')))]);

        return $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('part_brands', 'name')->ignore($brand)],
            'code' => ['required', 'string', 'max:20', 'alpha_num', Rule::unique('part_brands', 'code')->ignore($brand)],
            'country_of_origin' => ['nullable', 'string', 'max:100'],
            'is_oem_brand' => ['required', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
        ]);
    }
}
