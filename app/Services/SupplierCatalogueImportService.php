<?php

namespace App\Services;

use App\Exceptions\CatalogueSetupMissingException;
use App\Models\ApprovedSupplier;
use App\Models\Part;
use App\Models\PartBrand;
use App\Models\PartCategory;
use App\Models\Supplier;
use App\Models\UnitOfMeasure;

/**
 * Loads a wholesaler's product listing (item code · category · application
 * text · spec · UOM — no prices or stock) as catalogue parts linked to that
 * supplier. The supplier's categories are mapped onto our tree and their
 * application-only text becomes a searchable description via
 * database/data/catalogue_category_map.csv; bearing/piston size suffixes
 * (-0, -1 …) are spelled out via catalogue_size_suffixes.csv.
 *
 * Stateful per upload: call begin() once, then persist() per row.
 */
class SupplierCatalogueImportService
{
    /** Wizard/command field definitions — keyword order is match priority. */
    public const FIELDS = [
        'part_number' => ['label' => 'Item Code', 'required' => true, 'keywords' => ['item code', 'item', 'part no', 'part number', 'sku', 'code']],
        'description' => ['label' => 'Description', 'required' => true, 'keywords' => ['description', 'application', 'item name']],
        'category' => ['label' => 'Category', 'required' => false, 'keywords' => ['category', 'group']],
        'category_name' => ['label' => 'Category Desc', 'required' => false, 'keywords' => ['category desc', 'category name']],
        'specification' => ['label' => 'Specification', 'required' => false, 'keywords' => ['spec']],
        'unit' => ['label' => 'UOM', 'required' => false, 'keywords' => ['uom', 'unit']],
        'brand' => ['label' => 'Brand', 'required' => false, 'keywords' => ['brand', 'manufacturer', 'part brand', 'make of part']],
    ];

    public const EXAMPLE = ['AF213', 'SORENTO I 2.5CRDi 03-06', 'AF', 'AIR FILTER', '', 'PC', ''];

    public const DEFAULT_SUPPLIER = 'Catalogue Supplier';

    /** Brand used for unbranded aftermarket listings — honest default, filterable. */
    private const AFTERMARKET_BRAND = 'AFM';

    /** Supplier UOM codes that differ from our unit abbreviations. */
    private const UNIT_ALIASES = ['PC' => 'ea', 'PCS' => 'ea', 'EA' => 'ea', 'EACH' => 'ea', 'PAC' => 'pk', 'PKT' => 'pk', 'PACK' => 'pk', 'CRD' => 'card', 'BAL' => 'bale'];

    private const PLACEHOLDER = '/^NEW ITEM\W*$/i';

    private Supplier $supplier;

    /** @var array<string,array{label:string,keywords:array<int,string>,category_id:int,sized:bool}> keyed by normalised supplier name AND code */
    private array $map = [];

    /** @var array<string,string> */
    private array $sizes = [];

    /** @var array<string,int> lower-case abbreviation/name → unit id */
    private array $units = [];

    /** @var array<string,int> upper-case name/code → brand id */
    private array $brands = [];

    private int $fallbackBrandId;

    private int $fallbackUnitId;

    private int $unclassifiedId;

    /** @var array<int,true> part ids seen in this upload */
    private array $seen = [];

    /** @var array<string,int> supplier category → rows that fell into Unclassified */
    public array $unclassified = [];

    public int $placeholders = 0;

    /** Fitment rows derived from application text this run. */
    public int $linked = 0;

    public function __construct(private readonly NumberSequenceService $sequences) {}

    public function begin(string $supplierName): static
    {
        $categories = PartCategory::pluck('id', 'code');
        $rows = $this->csv('catalogue_category_map.csv');
        $missing = collect($rows)->pluck('category_code')->push('MISC')->unique()->reject(fn ($c) => isset($categories[$c]))->values()->all();
        if ($missing !== []) {
            throw new CatalogueSetupMissingException($missing);
        }

        $supplierName = trim($supplierName) ?: self::DEFAULT_SUPPLIER;
        $this->supplier = Supplier::where('name', $supplierName)->first()
            ?? Supplier::create(['supplier_number' => $this->sequences->next('supplier'), 'name' => $supplierName, 'type' => 'wholesaler', 'is_active' => true]);

        foreach ($rows as $r) {
            $entry = [
                'label' => strtoupper($r['label']),
                'keywords' => array_values(array_filter(explode('|', strtoupper($r['keywords'] ?: $r['label'])))),
                'category_id' => $categories[$r['category_code']],
                'sized' => $r['sized'] === '1',
            ];
            $this->map['name:'.$this->norm($r['supplier_category'])] = $entry;
            $this->map['code:'.$this->norm($r['supplier_code'])] = $entry;
        }
        foreach ($this->csv('catalogue_size_suffixes.csv') as $r) {
            $this->sizes[$r['suffix']] = $r['label'];
        }

        foreach (UnitOfMeasure::get(['id', 'name', 'abbreviation']) as $u) {
            $this->units[strtolower($u->abbreviation)] = $u->id;
            $this->units[strtolower($u->name)] = $u->id;
        }
        $this->fallbackUnitId = $this->units['ea'] ?? UnitOfMeasure::orderBy('id')->value('id');
        $this->unclassifiedId = $categories['MISC'];

        foreach (PartBrand::get(['id', 'name', 'code']) as $b) {
            $this->brands[strtoupper($b->name)] = $b->id;
            $this->brands[strtoupper($b->code)] = $b->id;
        }
        $this->fallbackBrandId = $this->brands[self::AFTERMARKET_BRAND]
            ?? PartBrand::firstOrCreate(['code' => self::AFTERMARKET_BRAND], ['name' => 'Aftermarket', 'is_oem_brand' => false])->id;
        $this->brands[self::AFTERMARKET_BRAND] = $this->fallbackBrandId;

        return $this;
    }

    public function supplier(): Supplier
    {
        return $this->supplier;
    }

    /** One listing row → 'created' | 'updated', or throws with the reason it was skipped. */
    public function persist(array $v): string
    {
        $code = strtoupper(trim((string) ($v['part_number'] ?? '')));
        $text = $this->clean($v['description'] ?? '');
        if ($code === '') {
            throw new \RuntimeException('Item code is empty.');
        }
        // Supplier placeholder rows ("NEW ITEM") are still real catalogue slots:
        // import them under the category label so the part links and searches —
        // a later listing fills the description in place.
        $placeholder = $text === '' || preg_match(self::PLACEHOLDER, $text);
        if ($placeholder) {
            $this->placeholders++;
        }

        $entry = $this->categoryFor($v);
        $description = $placeholder
            ? mb_substr(($entry['label'] ?? 'NEW ITEM')." — {$code} (description pending)", 0, 255)
            : $this->describe($code, $text, $this->clean($v['specification'] ?? ''), $entry);
        $supplierCategory = trim(($v['category_name'] ?? '') !== '' ? "{$v['category']} · {$v['category_name']}" : (string) ($v['category'] ?? ''));
        $attrs = [
            'description' => $description,
            'short_description' => mb_substr($description, 0, 100),
            'category_id' => $entry['category_id'] ?? $this->unclassifiedId,
            'brand_id' => $this->brandFor($v, $text.' '.$this->clean($v['specification'] ?? '')),
            'unit_id' => $this->unitFor($v['unit'] ?? ''),
        ];

        $part = Part::withTrashed()->where('part_number', $code)->first();
        if ($part?->trashed()) {
            throw new \RuntimeException("{$code}: this part was deleted in SparesPro — restore it on the Parts screen if you want the supplier listing to update it.");
        }
        if ($part) {
            // Barcode: the part number is the scannable code — backfill only,
            // never overwrite a captured EAN/Code128. Same for provenance notes.
            if ($part->barcode_code128 === null) {
                $attrs['barcode_code128'] = $code;
            }
            if ($part->notes === null) {
                $attrs['notes'] = trim("Imported: {$this->supplier->name} catalogue".($supplierCategory !== '' ? " ({$supplierCategory})" : '').($placeholder ? ' — supplier gave no description yet' : ''));
            }
            $part->fill($attrs)->save();
            $outcome = 'updated';
        } else {
            $part = Part::create($attrs + [
                'part_number' => $code,
                'barcode_code128' => $code,
                'notes' => trim("Imported: {$this->supplier->name} catalogue".($supplierCategory !== '' ? " ({$supplierCategory})" : '').($placeholder ? ' — supplier gave no description yet' : '')),
                'is_active' => true,
            ]);
            $outcome = 'created';
        }

        $this->linked += app(CatalogueFitmentService::class)->sync($part, $text.' '.$this->clean($v['specification'] ?? ''));

        $approved = ApprovedSupplier::where('part_id', $part->id)->where('supplier_id', $this->supplier->id)->first();
        if ($approved) {
            if ($approved->supplier_part_number === null) {
                $approved->update(['supplier_part_number' => $code]);
            }
        } else {
            ApprovedSupplier::create([
                'part_id' => $part->id, 'supplier_id' => $this->supplier->id,
                'supplier_part_number' => $code,
                'is_preferred' => ! ApprovedSupplier::where('part_id', $part->id)->where('is_preferred', true)->exists(),
            ]);
        }
        $this->seen[$part->id] = true;

        return $outcome;
    }

    /**
     * After a FULL listing upload: parts this supplier carries that were not in
     * the file are flagged discontinued (never deleted — stock stays sellable);
     * parts that reappeared are un-flagged. Returns [discontinued, revived].
     *
     * @return array{0:int,1:int}
     */
    public function syncDiscontinued(): array
    {
        $discontinued = $revived = 0;
        Part::whereIn('id', ApprovedSupplier::where('supplier_id', $this->supplier->id)->select('part_id'))
            ->chunkById(500, function ($parts) use (&$discontinued, &$revived) {
                foreach ($parts as $part) {
                    $listed = isset($this->seen[$part->id]);
                    if ($part->is_discontinued === $listed) {
                        $part->update(['is_discontinued' => ! $listed]);
                        $listed ? $revived++ : $discontinued++;
                    }
                }
            });

        return [$discontinued, $revived];
    }

    /**
     * "CONTROL ARM - HYU I10 09- LH", "MAIN BEARINGS - OPEL 1.6 82- SIZE 0.25mm (FRONT)".
     * The type label is prefixed only when the text doesn't already say what the part is.
     */
    public function describe(string $code, string $text, string $spec, ?array $entry): string
    {
        $text = rtrim($text, '. ');
        $upper = strtoupper($text);
        $label = $entry['label'] ?? '';
        $named = $label === '' || collect($entry['keywords'])->contains(fn ($k) => preg_match('/\b'.preg_quote($k, '/').'/', $upper));
        $out = $named ? $text : "{$label} - {$text}";

        if (($entry['sized'] ?? false) && preg_match('/-(\d+)$/', $code, $m)) {
            $out .= ' SIZE '.($this->sizes[$m[1]] ?? $m[1]);
        }
        if ($spec !== '' && $spec !== '0' && ! str_contains($upper, strtoupper($spec))) {
            $out .= " ({$spec})";
        }

        return mb_substr($out, 0, 255);
    }

    private function categoryFor(array $v): ?array
    {
        foreach (['category_name' => 'name:', 'category' => 'name:'] as $field => $prefix) {
            $key = $prefix.$this->norm($v[$field] ?? '');
            if (isset($this->map[$key])) {
                return $this->map[$key];
            }
        }
        $code = 'code:'.$this->norm($v['category'] ?? '');
        if (isset($this->map[$code])) {
            return $this->map[$code];
        }

        $name = trim(($v['category_name'] ?? '') ?: ($v['category'] ?? '')) ?: '(none)';
        $this->unclassified[$name] = ($this->unclassified[$name] ?? 0) + 1;

        return null;
    }

    /**
     * Brand: explicit column wins, then a named brand in the application text
     * ("BOSCH", "DENSO"), else the honest "Aftermarket" fallback.
     */
    private function brandFor(array $v, string $text): int
    {
        $named = $this->norm($v['brand'] ?? '');
        if ($named !== '' && isset($this->brands[$named])) {
            return $this->brands[$named];
        }
        foreach ($this->brands as $name => $id) {
            if ($name !== self::AFTERMARKET_BRAND && strlen($name) >= 3 && preg_match('/\b'.preg_quote($name, '/').'\b/', strtoupper($text))) {
                return $id;
            }
        }

        return $this->fallbackBrandId;
    }

    private function unitFor(string $raw): int
    {
        $raw = strtoupper(trim($raw));
        $key = strtolower(self::UNIT_ALIASES[$raw] ?? $raw);

        return $this->units[$key] ?? $this->fallbackUnitId;
    }

    private function clean(?string $s): string
    {
        return trim(preg_replace('/\s+/', ' ', (string) $s));
    }

    private function norm(?string $s): string
    {
        return strtoupper($this->clean($s));
    }

    /** @return array<int,array<string,string>> */
    private function csv(string $file): array
    {
        $handle = fopen(database_path("data/{$file}"), 'r');
        $header = fgetcsv($handle, null, ',', '"', '');
        $rows = [];
        while (($row = fgetcsv($handle, null, ',', '"', '')) !== false) {
            if (count($row) === count($header)) {
                $rows[] = array_combine($header, array_map('trim', $row));
            }
        }
        fclose($handle);

        return $rows;
    }
}
