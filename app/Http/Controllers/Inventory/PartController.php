<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Part;
use App\Models\PartBrand;
use App\Models\PartCategory;
use App\Models\UnitOfMeasure;
use App\Models\VehicleMake;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PartController extends Controller
{
    public function index(Request $request): Response
    {
        $parts = Part::with(['category:id,name', 'brand:id,name',
            'supersededBy.newPart:id,part_number', 'stockLevels'])
            ->when($request->string('search')->toString(), fn ($q, $s) => $q->search($s))
            ->when($request->integer('category_id'), fn ($q, $id) => $q->where('category_id', $id))
            ->when($request->string('status')->toString() === 'active', fn ($q) => $q->where('is_active', true))
            ->when($request->string('status')->toString() === 'inactive', fn ($q) => $q->where('is_active', false))
            ->orderBy('part_number')
            ->paginate(25)
            ->withQueryString()
            ->through(fn (Part $p) => [
                'id' => $p->id,
                'part_number' => $p->part_number,
                'oem_number' => $p->oem_number,
                'description' => $p->description,
                'category' => $p->category?->name,
                'brand' => $p->brand?->name,
                'is_oem' => $p->is_oem,
                'is_active' => $p->is_active,
                'qty_available' => (float) $p->stockLevels->sum(
                    fn ($l) => (float) $l->qty_on_hand - (float) $l->qty_reserved
                ),
                'superseded_by' => $p->supersededBy?->newPart?->part_number,
            ]);

        return Inertia::render('Inventory/Parts/Index', [
            'parts' => $parts,
            'categories' => PartCategory::active()->orderBy('sort_order')->get(['id', 'name', 'parent_id']),
            'filters' => $request->only('search', 'category_id', 'status'),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Inventory/Parts/Create', $this->formOptions());
    }

    public function store(Request $request): RedirectResponse
    {
        $part = Part::create($this->validated($request));

        return redirect()->route('inventory.parts.show', $part)
            ->with('success', "Part {$part->part_number} created.");
    }

    public function show(Part $part): Response
    {
        $part->load([
            'category:id,name', 'brand:id,name', 'unit:id,name,abbreviation',
            'stockLevels.branch:id,name', 'stockLevels.binLocation:id,code',
            'crossReferences.brand:id,name',
            'fitments.make:id,name', 'fitments.model:id,name', 'fitments.variant:id,name',
            'supersededBy.newPart:id,part_number,description',
            'supersedes.oldPart:id,part_number,description',
        ]);

        return Inertia::render('Inventory/Parts/Show', [
            'part' => [
                'id' => $part->id,
                'part_number' => $part->part_number,
                'oem_number' => $part->oem_number,
                'description' => $part->description,
                'short_description' => $part->short_description,
                'category' => $part->category?->name,
                'brand' => $part->brand?->name,
                'unit' => $part->unit?->abbreviation,
                'barcode_ean' => $part->barcode_ean,
                'is_oem' => $part->is_oem,
                'is_active' => $part->is_active,
                'is_discontinued' => $part->is_discontinued,
                'notes' => $part->notes,
                'stock' => $part->stockLevels->map(fn ($l) => [
                    'branch' => $l->branch?->name,
                    'bin' => $l->binLocation?->code,
                    'qty_on_hand' => (float) $l->qty_on_hand,
                    'qty_reserved' => (float) $l->qty_reserved,
                    'qty_available' => (float) $l->qty_on_hand - (float) $l->qty_reserved,
                    'average_cost' => (float) $l->average_cost,
                ]),
                'cross_references' => $part->crossReferences->map(fn ($x) => [
                    'id' => $x->id,
                    'number' => $x->reference_number,
                    'brand' => $x->brand?->name,
                    'type' => $x->type,
                ]),
                'fitments' => $part->fitments->map(fn ($f) => [
                    'id' => $f->id,
                    'make' => $f->make?->name,
                    'model' => $f->model?->name,
                    'variant' => $f->variant?->name,
                    'years' => trim(($f->year_from ?? '').'–'.($f->year_to ?? ''), '–') ?: null,
                    'engine_code' => $f->engine_code,
                    'confirmed' => $f->confirmed,
                ]),
                'superseded_by' => $part->supersededBy?->newPart ? [
                    'id' => $part->supersededBy->newPart->id,
                    'part_number' => $part->supersededBy->newPart->part_number,
                ] : null,
                'supersedes' => $part->supersedes->map(fn ($s) => [
                    'id' => $s->oldPart?->id,
                    'part_number' => $s->oldPart?->part_number,
                ])->filter()->values(),
            ],
            'options' => [
                'makes' => VehicleMake::active()->orderBy('name')->get(['id', 'name']),
                'brands' => PartBrand::active()->orderBy('name')->get(['id', 'name']),
            ],
        ]);
    }

    public function edit(Part $part): Response
    {
        return Inertia::render('Inventory/Parts/Edit', [
            'part' => $part->only([
                'id', 'part_number', 'oem_number', 'description', 'short_description',
                'category_id', 'brand_id', 'unit_id', 'barcode_ean', 'weight_kg',
                'is_oem', 'is_active', 'is_discontinued', 'notes',
            ]),
        ] + $this->formOptions());
    }

    public function update(Request $request, Part $part): RedirectResponse
    {
        $part->update($this->validated($request, $part));

        return redirect()->route('inventory.parts.show', $part)
            ->with('success', "Part {$part->part_number} updated.");
    }

    private function validated(Request $request, ?Part $part = null): array
    {
        return $request->validate([
            'part_number' => ['required', 'string', 'max:50', 'unique:parts,part_number'.($part ? ','.$part->id : '')],
            'oem_number' => ['nullable', 'string', 'max:100'],
            'description' => ['required', 'string', 'max:255'],
            'short_description' => ['nullable', 'string', 'max:100'],
            'category_id' => ['required', 'exists:part_categories,id'],
            'brand_id' => ['nullable', 'exists:part_brands,id'],
            'unit_id' => ['required', 'exists:units_of_measure,id'],
            'barcode_ean' => ['nullable', 'string', 'max:20', 'unique:parts,barcode_ean'.($part ? ','.$part->id : '')],
            'weight_kg' => ['nullable', 'numeric', 'min:0', 'max:9999'],
            'is_oem' => ['required', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
            'is_discontinued' => ['sometimes', 'boolean'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
    }

    private function formOptions(): array
    {
        return [
            'categories' => PartCategory::active()->orderBy('sort_order')->get(['id', 'name', 'parent_id']),
            'brands' => PartBrand::active()->orderBy('name')->get(['id', 'name']),
            'units' => UnitOfMeasure::orderBy('name')->get(['id', 'name', 'abbreviation']),
        ];
    }
}
