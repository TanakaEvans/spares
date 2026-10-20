<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\ApprovedSupplier;
use App\Models\GoodsReceivedNote;
use App\Models\JobCard;
use App\Models\Part;
use App\Models\PartBrand;
use App\Models\PartCategory;
use App\Models\PriceListItem;
use App\Models\SalesDocument;
use App\Models\StockAdjustment;
use App\Models\StockLedgerEntry;
use App\Models\StockTake;
use App\Models\StockTransfer;
use App\Models\SupplierReturn;
use App\Models\UnitOfMeasure;
use App\Models\VehicleMake;
use App\Support\ListExport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class PartController extends Controller
{
    /** Parts catalogue as a CSV or PDF export (honours search/category/status filters). */
    public function export(Request $request): HttpResponse
    {
        $rows = Part::with(['category:id,name', 'brand:id,name', 'stockLevels'])
            ->when($request->string('search')->toString(), fn ($q, $s) => $q->search($s))
            ->when($request->integer('category_id'), fn ($q, $id) => $q->where('category_id', $id))
            ->when($request->string('status')->toString() === 'active', fn ($q) => $q->where('is_active', true))
            ->when($request->string('status')->toString() === 'inactive', fn ($q) => $q->where('is_active', false))
            ->orderBy('part_number')->get()
            ->map(fn (Part $p) => [
                $p->part_number, $p->oem_number, $p->description,
                $p->category?->name, $p->brand?->name,
                $p->is_oem ? 'OEM' : 'Aftermarket',
                number_format((float) $p->stockLevels->sum('qty_on_hand'), 2, '.', ''),
                $p->is_active ? 'active' : 'inactive',
            ]);

        return ListExport::respond($request, 'parts', 'Parts Catalogue', [
            'Part #', 'OEM #', 'Description', 'Category', 'Brand', 'Type', 'On hand', 'Status',
        ], $rows);
    }

    public function index(Request $request): Response
    {
        $parts = Part::with(['category:id,name', 'brand:id,name',
            'supersededBy.newPart:id,part_number', 'stockLevels'])
            ->when($request->string('search')->toString(), fn ($q, $s) => $q->search($s))
            ->when($request->integer('category_id'), fn ($q, $id) => $q->where('category_id', $id))
            ->when($request->integer('brand_id'), fn ($q, $id) => $q->where('brand_id', $id))
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
                'category_id' => $p->category_id,
                'category' => $p->category?->name,
                'brand_id' => $p->brand_id,
                'brand' => $p->brand?->name,
                'is_oem' => $p->is_oem,
                'is_active' => $p->is_active,
                'qty_available' => (float) $p->stockLevels->sum(
                    fn ($l) => (float) $l->qty_on_hand - (float) $l->qty_reserved
                ),
                'superseded_by_id' => $p->supersededBy?->newPart?->id,
                'superseded_by' => $p->supersededBy?->newPart?->part_number,
            ]);

        return Inertia::render('Inventory/Parts/Index', [
            'parts' => $parts,
            'categories' => PartCategory::active()->orderBy('sort_order')->get(['id', 'name', 'parent_id']),
            'brands' => PartBrand::active()->orderBy('name')->get(['id', 'name']),
            'filters' => $request->only('search', 'category_id', 'brand_id', 'status'),
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

        // Approved suppliers, preferred first (Module 3.4) — read-only.
        $suppliers = ApprovedSupplier::with('supplier:id,name,supplier_number')
            ->where('part_id', $part->id)
            ->orderByDesc('is_preferred')
            ->get()
            ->filter(fn ($a) => $a->supplier)
            ->map(fn ($a) => [
                'id' => $a->supplier_id,
                'name' => $a->supplier->name,
                'supplier_number' => $a->supplier->supplier_number,
                'is_preferred' => (bool) $a->is_preferred,
                'lead_time_days' => $a->lead_time_days,
            ])->values();

        // Every price list this part is priced on.
        $priceLists = PriceListItem::with('priceList:id,name,type,is_active')
            ->where('part_id', $part->id)
            ->get()
            ->filter(fn ($i) => $i->priceList)
            ->map(fn ($i) => [
                'id' => $i->price_list_id,
                'name' => $i->priceList->name,
                'type' => $i->priceList->type,
                'price' => (float) $i->price,
            ])->values();

        // Cross-reference numbers that are themselves catalogue parts (by part/OEM number).
        $xrefNumbers = $part->crossReferences->pluck('reference_number')->filter()->unique()->values();
        $xrefParts = $xrefNumbers->isEmpty() ? collect() : Part::query()
            ->where('id', '!=', $part->id)
            ->where(fn ($q) => $q->whereIn('part_number', $xrefNumbers)->orWhereIn('oem_number', $xrefNumbers))
            ->get(['id', 'part_number', 'oem_number']);

        return Inertia::render('Inventory/Parts/Show', [
            'part' => [
                'id' => $part->id,
                'part_number' => $part->part_number,
                'oem_number' => $part->oem_number,
                'description' => $part->description,
                'short_description' => $part->short_description,
                'category_id' => $part->category_id,
                'category' => $part->category?->name,
                'brand_id' => $part->brand_id,
                'brand' => $part->brand?->name,
                'unit' => $part->unit?->abbreviation,
                'barcode_ean' => $part->barcode_ean,
                'is_oem' => $part->is_oem,
                'is_active' => $part->is_active,
                'is_discontinued' => $part->is_discontinued,
                'notes' => $part->notes,
                'stock' => $part->stockLevels->map(fn ($l) => [
                    'id' => $l->id,
                    'branch_id' => $l->branch_id,
                    'branch' => $l->branch?->name,
                    'bin_id' => $l->bin_location_id,
                    'bin' => $l->binLocation?->code,
                    'qty_on_hand' => (float) $l->qty_on_hand,
                    'qty_reserved' => (float) $l->qty_reserved,
                    'qty_available' => (float) $l->qty_on_hand - (float) $l->qty_reserved,
                    'average_cost' => (float) $l->average_cost,
                ]),
                'cross_references' => $part->crossReferences->map(function ($x) use ($xrefParts) {
                    $match = $xrefParts->first(fn ($p) => $p->part_number === $x->reference_number || $p->oem_number === $x->reference_number);

                    return [
                        'id' => $x->id,
                        'number' => $x->reference_number,
                        'brand_id' => $x->brand_id,
                        'brand' => $x->brand?->name,
                        'type' => $x->type,
                        'part_id' => $match?->id,
                        'part_number' => $match?->part_number,
                    ];
                }),
                'fitments' => $part->fitments->map(fn ($f) => [
                    'id' => $f->id,
                    'make_id' => $f->make_id,
                    'make' => $f->make?->name,
                    'model_id' => $f->model_id,
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
                ])->filter(fn ($s) => $s['id'])->values(),
                'suppliers' => $suppliers,
                'price_lists' => $priceLists,
                'movements' => $this->recentMovements($part, 15),
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

    /**
     * Last N stock-ledger entries for the part, each with its source document
     * resolved to an EntityLink type + label so the page can hyperlink it.
     * The reference_type strings are exactly what the posting services write.
     */
    private function recentMovements(Part $part, int $limit): Collection
    {
        $entries = StockLedgerEntry::with('branch:id,name')
            ->where('part_id', $part->id)
            ->orderByDesc('id')
            ->limit($limit)
            ->get();

        $idsFor = fn (array $types) => $entries
            ->filter(fn ($e) => in_array($e->reference_type, $types, true))
            ->pluck('reference_id')->filter()->unique()->values();

        $sales = SalesDocument::whereIn('id', $idsFor([SalesDocument::class]))->get(['id', 'document_number', 'document_type'])->keyBy('id');
        $grns = GoodsReceivedNote::whereIn('id', $idsFor(['GoodsReceivedNote', GoodsReceivedNote::class]))->get(['id', 'grn_number'])->keyBy('id');
        $adjustments = StockAdjustment::whereIn('id', $idsFor([StockAdjustment::class]))->get(['id', 'adjustment_number'])->keyBy('id');
        $transfers = StockTransfer::whereIn('id', $idsFor([StockTransfer::class]))->get(['id', 'transfer_number'])->keyBy('id');
        $jobs = JobCard::whereIn('id', $idsFor([JobCard::class]))->get(['id', 'job_number'])->keyBy('id');
        $returns = SupplierReturn::whereIn('id', $idsFor([SupplierReturn::class]))->get(['id', 'return_number'])->keyBy('id');
        // A stock take posts through one adjustment — surface the take too.
        $takes = StockTake::whereIn('adjustment_id', $adjustments->keys())->get(['id', 'take_number', 'adjustment_id'])->keyBy('adjustment_id');

        $salesType = ['invoice' => 'invoice', 'credit_note' => 'credit_note', 'quotation' => 'quote', 'order' => 'order'];

        return $entries->map(function (StockLedgerEntry $e) use ($sales, $grns, $adjustments, $transfers, $jobs, $returns, $takes, $salesType) {
            $ref = null;
            $extra = null;
            $id = $e->reference_id;

            switch ($e->reference_type) {
                case SalesDocument::class:
                    if ($doc = $sales->get($id)) {
                        $ref = ['type' => $salesType[$doc->document_type] ?? 'invoice', 'id' => $doc->id, 'label' => $doc->document_number];
                    }
                    break;
                case 'GoodsReceivedNote':
                case GoodsReceivedNote::class:
                    if ($grn = $grns->get($id)) {
                        $ref = ['type' => 'grn', 'id' => $grn->id, 'label' => $grn->grn_number];
                    }
                    break;
                case StockAdjustment::class:
                    if ($adj = $adjustments->get($id)) {
                        $ref = ['type' => 'adjustment', 'id' => $adj->id, 'label' => $adj->adjustment_number];
                        if ($take = $takes->get($id)) {
                            $extra = ['type' => 'stock_take', 'id' => $take->id, 'label' => $take->take_number];
                        }
                    }
                    break;
                case StockTransfer::class:
                    if ($t = $transfers->get($id)) {
                        $ref = ['type' => 'transfer', 'id' => $t->id, 'label' => $t->transfer_number];
                    }
                    break;
                case JobCard::class:
                    if ($job = $jobs->get($id)) {
                        $ref = ['type' => 'job', 'id' => $job->id, 'label' => $job->job_number];
                    }
                    break;
                case SupplierReturn::class:
                    if ($r = $returns->get($id)) {
                        $ref = ['type' => 'supplier_return', 'id' => $r->id, 'label' => $r->return_number];
                    }
                    break;
            }

            return [
                'id' => $e->id,
                'date' => $e->created_at?->toDateTimeString(),
                'type' => $e->transaction_type,
                'branch_id' => $e->branch_id,
                'branch' => $e->branch?->name,
                'qty' => (float) $e->qty,
                'unit_cost' => (float) $e->unit_cost,
                'running_balance' => (float) $e->running_balance,
                'notes' => $e->notes,
                'reference' => $ref,
                'reference_extra' => $extra,
                // Plain-text fallback for sources that have no page (seeders, tests).
                'reference_text' => $ref ? null : ($e->reference_type ? class_basename($e->reference_type).($id ? " #{$id}" : '') : null),
            ];
        })->values();
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
