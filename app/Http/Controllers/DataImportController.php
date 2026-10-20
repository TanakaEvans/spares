<?php

namespace App\Http\Controllers;

use App\Models\ApprovedSupplier;
use App\Models\BinLocation;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\DataImport;
use App\Models\Part;
use App\Models\PartBrand;
use App\Models\PartCategory;
use App\Models\PriceList;
use App\Models\PriceListItem;
use App\Models\StockLevel;
use App\Models\Supplier;
use App\Models\SupplierPriceList;
use App\Models\UnitOfMeasure;
use App\Services\CsvImportService;
use App\Services\GlPostingService;
use App\Services\NumberSequenceService;
use App\Services\OpeningBalanceService;
use App\Services\SettingsService;
use App\Services\StockLedgerService;
use App\Support\CsvExport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

/**
 * Data Upload: one place for staff to load the business's real data from
 * Excel/CSV — Parts & Stock (creates parts, prices them, posts opening stock
 * through the stock ledger, places bins, links the preferred supplier),
 * Suppliers and Customers. Upload → map columns → commit → results.
 * Rows match on their natural key, so re-uploading corrects, never duplicates.
 */
class DataImportController extends Controller
{
    private const ENTITIES = ['stock', 'parts', 'suppliers', 'customers'];

    /** Per-entity definitions: friendly labels double as template headers. */
    private function entities(): array
    {
        return [
            'stock' => [
                'label' => 'Parts & Stock',
                'icon' => 'Boxes',
                'listRoute' => 'inventory.stock.index',
                'exportRoute' => 'inventory.stock.export',
                'blurb' => 'Your whole stock in one sheet: each row is a part with its quantity on hand and cost. Selling price, bin and supplier are optional.',
                'fields' => [
                    'part_number' => ['label' => 'Part Number', 'required' => true, 'keywords' => ['part no', 'part number', 'part #', 'sku', 'item code']],
                    'description' => ['label' => 'Description', 'required' => true, 'keywords' => ['desc', 'item name', 'product']],
                    'oem_number' => ['label' => 'OEM Number', 'required' => false, 'keywords' => ['oem', 'genuine']],
                    'barcode' => ['label' => 'Barcode', 'required' => false, 'keywords' => ['barcode', 'ean', 'upc']],
                    'category' => ['label' => 'Category', 'required' => false, 'keywords' => ['categ', 'group']],
                    'brand' => ['label' => 'Brand', 'required' => false, 'keywords' => ['brand', 'manufact']],
                    'unit' => ['label' => 'Unit', 'required' => false, 'keywords' => ['unit', 'uom', 'measure']],
                    'cost_price' => ['label' => 'Cost Price (excl VAT)', 'required' => false, 'keywords' => ['cost', 'buy']],
                    'selling_price_incl_vat' => ['label' => 'Selling Price (incl VAT)', 'required' => false, 'keywords' => ['incl', 'retail', 'sell']],
                    'selling_price_excl_vat' => ['label' => 'Selling Price (excl VAT)', 'required' => false, 'keywords' => ['excl']],
                    'qty_on_hand' => ['label' => 'Qty On Hand', 'required' => false, 'keywords' => ['qty', 'quantity', 'on hand', 'stock']],
                    'bin' => ['label' => 'Bin', 'required' => false, 'keywords' => ['bin', 'shelf', 'location']],
                    'reorder_point' => ['label' => 'Reorder Point', 'required' => false, 'keywords' => ['reorder point', 'reorder level', 'minimum']],
                    'reorder_qty' => ['label' => 'Reorder Qty', 'required' => false, 'keywords' => ['reorder qty', 'order qty']],
                    'supplier' => ['label' => 'Supplier', 'required' => false, 'keywords' => ['supplier', 'vendor']],
                ],
                'example' => ['Z762', 'Oil filter — Toyota Hilux 2.8 GD-6', '90915-YZZD4', '6001234567890', 'Oil Filters', 'GUD', 'Each', '85.00', '149.00', '', '24', 'A-01-01', '5', '20', 'GUD Distributors'],
            ],
            'parts' => [
                'label' => 'Parts (catalogue only)',
                'icon' => 'PackageSearch',
                'listRoute' => 'inventory.parts.index',
                'exportRoute' => 'inventory.parts.export',
                'blurb' => 'Catalogue entries without quantities. Use Parts & Stock if you also have stock on hand.',
                'fields' => [
                    'part_number' => ['label' => 'Part Number', 'required' => true, 'keywords' => ['part no', 'part number', 'part #', 'sku', 'item code']],
                    'description' => ['label' => 'Description', 'required' => true, 'keywords' => ['desc', 'item name', 'product']],
                    'oem_number' => ['label' => 'OEM Number', 'required' => false, 'keywords' => ['oem', 'genuine']],
                    'category' => ['label' => 'Category', 'required' => false, 'keywords' => ['categ', 'group']],
                    'brand' => ['label' => 'Brand', 'required' => false, 'keywords' => ['brand', 'manufact']],
                    'unit' => ['label' => 'Unit', 'required' => false, 'keywords' => ['unit', 'uom', 'measure']],
                ],
                'example' => ['Z762', 'Oil filter — Toyota Hilux 2.8 GD-6', '90915-YZZD4', 'Oil Filters', 'GUD', 'Each'],
            ],
            'suppliers' => [
                'label' => 'Suppliers',
                'icon' => 'Truck',
                'listRoute' => 'suppliers.index',
                'exportRoute' => 'suppliers.export',
                'blurb' => 'Who you buy from. Leave the supplier number blank and the system assigns one.',
                'fields' => [
                    'name' => ['label' => 'Supplier Name', 'required' => true, 'keywords' => ['name', 'supplier', 'company']],
                    'supplier_number' => ['label' => 'Supplier Number (blank = auto)', 'required' => false, 'keywords' => ['number', 'account', 'code']],
                    'type' => ['label' => 'Type (local/import/manufacturer)', 'required' => false, 'keywords' => ['type']],
                    'phone' => ['label' => 'Phone', 'required' => false, 'keywords' => ['phone', 'tel', 'cell']],
                    'email' => ['label' => 'Email', 'required' => false, 'keywords' => ['email', 'mail']],
                    'city' => ['label' => 'City', 'required' => false, 'keywords' => ['city', 'town']],
                    'lead_time_days' => ['label' => 'Lead Time (days)', 'required' => false, 'keywords' => ['lead']],
                    'payment_terms_days' => ['label' => 'Payment Terms (days)', 'required' => false, 'keywords' => ['terms']],
                ],
                'example' => ['GUD Distributors', '', 'local', '+27 15 123 4567', 'orders@gud.example', 'Polokwane', '3', '30'],
            ],
            'customers' => [
                'label' => 'Customers',
                'icon' => 'Users',
                'listRoute' => 'customers.index',
                'exportRoute' => 'customers.export',
                'blurb' => 'Account and regular customers. Walk-in cash sales need no customer record.',
                'fields' => [
                    'name' => ['label' => 'Customer Name', 'required' => true, 'keywords' => ['name', 'customer', 'company']],
                    'customer_number' => ['label' => 'Customer Number (blank = auto)', 'required' => false, 'keywords' => ['number', 'account', 'code']],
                    'type' => ['label' => 'Type (individual/business/fleet/dealer)', 'required' => false, 'keywords' => ['type']],
                    'phone' => ['label' => 'Phone', 'required' => false, 'keywords' => ['phone', 'tel', 'cell']],
                    'email' => ['label' => 'Email', 'required' => false, 'keywords' => ['email', 'mail']],
                    'city' => ['label' => 'City', 'required' => false, 'keywords' => ['city', 'town']],
                    'credit_limit' => ['label' => 'Credit Limit', 'required' => false, 'keywords' => ['credit', 'limit']],
                    'payment_terms_days' => ['label' => 'Payment Terms (days)', 'required' => false, 'keywords' => ['terms']],
                ],
                'example' => ['Bochum Taxi Association', '', 'fleet', '+27 76 000 0000', 'accounts@example.com', 'Bochum', '5000', '30'],
            ],
        ];
    }

    // ── Hub ──────────────────────────────────────────────────────────────

    public function index(OpeningBalanceService $opening, GlPostingService $gl): Response
    {
        $branch = $this->branch();
        $counts = [
            'parts' => Part::count(),
            'stocked' => StockLevel::where('qty_on_hand', '>', 0)->count(),
            'suppliers' => Supplier::count(),
            'customers' => Customer::where('is_walk_in', false)->count(),
        ];
        $stockValue = round($opening->stockValue(), 2);
        $glInventory = round($gl->accountBalance('1310'), 2);

        return Inertia::render('Imports/Index', [
            'steps' => collect($this->entities())->map(fn ($e, $k) => [
                'entity' => $k, 'label' => $e['label'], 'icon' => $e['icon'], 'blurb' => $e['blurb'],
                'count' => match ($k) {
                    'stock' => $counts['stocked'], 'parts' => $counts['parts'],
                    'suppliers' => $counts['suppliers'], 'customers' => $counts['customers'],
                },
                'countLabel' => match ($k) {
                    'stock' => 'parts with stock', 'parts' => 'parts in catalogue',
                    'suppliers' => 'suppliers', 'customers' => 'customers',
                },
                'listRoute' => $e['listRoute'],
            ])->values(),
            'counts' => $counts,
            'opening' => [
                'stock_value' => $stockValue,
                'gl_inventory' => $glInventory,
                'gap' => round($stockValue - $glInventory, 2),
            ],
            'branch' => $branch->only(['id', 'name']),
            'recent' => DataImport::with('user:id,name')->latest()->limit(10)->get()->map(fn (DataImport $i) => [
                'id' => $i->id, 'entity' => $this->entities()[$i->entity]['label'] ?? $i->entity,
                'file_name' => $i->file_name, 'user' => $i->user?->name,
                'created' => $i->created_count, 'updated' => $i->updated_count, 'skipped' => $i->skipped_count,
                'errors' => count($i->errors ?? []), 'at' => $i->created_at->diffForHumans(),
            ]),
        ]);
    }

    /** A ready-to-fill template: the exact headers the wizard maps automatically, plus one example row. */
    public function template(string $entity): HttpResponse
    {
        $config = $this->config($entity);
        $headers = array_column($config['fields'], 'label');

        return CsvExport::download("{$entity}-upload-template", $headers, [$config['example']]);
    }

    public function result(DataImport $import): Response
    {
        $config = $this->entities()[$import->entity] ?? null;

        return Inertia::render('Imports/Result', [
            'import' => [
                'id' => $import->id, 'entity' => $import->entity, 'label' => $config['label'] ?? $import->entity,
                'file_name' => $import->file_name, 'created' => $import->created_count,
                'updated' => $import->updated_count, 'skipped' => $import->skipped_count,
                'errors' => $import->errors ?? [], 'at' => $import->created_at->format('d M Y H:i'),
                'listRoute' => $config['listRoute'] ?? 'imports.index',
            ],
            'needsOpeningPost' => $import->entity === 'stock' && abs(app(OpeningBalanceService::class)->inventoryGlGap()) > 0.009,
        ]);
    }

    // ── Wizard ───────────────────────────────────────────────────────────

    public function show(string $entity): Response
    {
        return Inertia::render('Imports/Wizard', $this->meta($entity));
    }

    /** Step 1: store the upload (Excel converted to CSV), preview + guess the column map. */
    public function preview(Request $request, string $entity, CsvImportService $csv): Response|RedirectResponse
    {
        $config = $this->config($entity);
        $request->validate(['file' => ['required', 'file', 'mimes:csv,txt,xlsx,xls', 'max:20480']]);

        try {
            $path = $csv->storeUpload($request->file('file'));
        } catch (\RuntimeException $e) {
            return back()->withErrors(['file' => $e->getMessage()]);
        }
        [$header, $rows] = $csv->parse(storage_path('app/private/'.$path));

        if ($header === [] || $rows === []) {
            return back()->withErrors(['file' => 'That file has no data rows. Fill in the template and upload it again.']);
        }

        $guess = [];
        foreach ($config['fields'] as $key => $def) {
            $guess[$key] = $csv->guess($header, $def['keywords'], $def['label'], $key);
        }

        return Inertia::render('Imports/Wizard', array_merge($this->meta($entity), [
            'preview' => [
                'path' => $path, 'original_name' => $request->file('file')->getClientOriginalName(),
                'header' => $header, 'rows' => $rows, 'guess' => $guess,
            ],
        ]));
    }

    /** Step 2: commit the mapped rows, log the import, show the results. */
    public function store(Request $request, string $entity, CsvImportService $csv, NumberSequenceService $sequences, StockLedgerService $ledger, SettingsService $settings): RedirectResponse
    {
        $config = $this->config($entity);
        $data = $request->validate([
            'path' => ['required', 'string'],
            'original_name' => ['nullable', 'string', 'max:255'],
            'map' => ['required', 'array'],
        ]);

        foreach ($config['fields'] as $key => $def) {
            if (($def['required'] ?? false) && ! is_numeric($data['map'][$key] ?? null)) {
                return back()->withErrors(['map' => "Please match the required column: {$def['label']}."]);
            }
        }

        $file = storage_path('app/private/'.$data['path']);
        abort_unless(is_file($file) && str_contains($data['path'], 'imports/data'), 404);

        $import = DataImport::create([
            'entity' => $entity, 'file_name' => $data['original_name'] ?? basename($data['path']),
            'user_id' => $request->user()?->id,
        ]);
        $userId = $request->user()?->id;
        $branch = $this->branch();
        $vat = (float) $settings->get('tax.vat_rate_default');

        $persist = match ($entity) {
            'stock' => fn (array $v) => $this->persistStock($v, $branch, $vat, $sequences, $ledger, $import->id, $userId),
            'parts' => fn (array $v) => $this->persistPart($v),
            'suppliers' => fn (array $v) => $this->persistSupplier($v, $sequences),
            'customers' => fn (array $v) => $this->persistCustomer($v, $sequences),
        };

        $result = $csv->import($file, $data['map'], $persist);
        @unlink($file);

        $import->update([
            'created_count' => $result['created'], 'updated_count' => $result['updated'],
            'skipped_count' => $result['skipped'], 'errors' => $result['errors'],
        ]);

        return redirect()->route('imports.result', $import)
            ->with('success', sprintf('%s upload: %d created, %d updated, %d skipped.',
                $config['label'], $result['created'], $result['updated'], $result['skipped']));
    }

    // ── Persisters ───────────────────────────────────────────────────────

    /** Parts & Stock: part → price → opening stock (via the ledger) → bin/reorder → supplier link. */
    private function persistStock(array $v, Branch $branch, float $vat, NumberSequenceService $sequences, StockLedgerService $ledger, int $importId, ?int $userId): string
    {
        $outcome = $this->persistPart($v);
        $part = Part::where('part_number', $v['part_number'])->firstOrFail();

        if (($v['barcode'] ?? '') !== '' && $part->barcode_ean !== $v['barcode']) {
            $part->update(['barcode_ean' => $v['barcode']]);
        }

        // Selling price → default retail list (stored VAT-exclusive).
        $excl = $this->num($v['selling_price_excl_vat'] ?? null);
        $incl = $this->num($v['selling_price_incl_vat'] ?? null);
        if ($excl <= 0 && $incl > 0) {
            $excl = round($incl / (1 + $vat / 100), 2);
        }
        if ($excl > 0) {
            $retail = PriceList::where('is_default', true)->first();
            if ($retail) {
                PriceListItem::updateOrCreate(['price_list_id' => $retail->id, 'part_id' => $part->id], ['price' => $excl]);
            }
        }

        // Opening stock — through the ledger, once. A part that already has stock is left alone.
        $qty = $this->num($v['qty_on_hand'] ?? null);
        $cost = $this->num($v['cost_price'] ?? null);
        $level = StockLevel::where('part_id', $part->id)->where('branch_id', $branch->id)->first();
        if ($qty > 0 && (! $level || (float) $level->qty_on_hand <= 0)) {
            if ($cost <= 0) {
                throw new \RuntimeException("{$part->part_number}: a cost price is needed to load {$qty} on hand.");
            }
            $ledger->post($part->id, $branch, 'OPENING_BALANCE', $qty, $cost, 'DataImport', $importId, 'Opening stock upload', $userId);
            $level = StockLevel::where('part_id', $part->id)->where('branch_id', $branch->id)->first();
        }

        // Bin + reorder levels (non-movement attributes).
        $attrs = [];
        if (($v['bin'] ?? '') !== '') {
            $attrs['bin_location_id'] = BinLocation::firstOrCreate(['branch_id' => $branch->id, 'code' => strtoupper($v['bin'])], ['is_active' => true])->id;
        }
        if (($v['reorder_point'] ?? '') !== '') {
            $attrs['reorder_point'] = $this->num($v['reorder_point']);
        }
        if (($v['reorder_qty'] ?? '') !== '') {
            $attrs['reorder_qty'] = $this->num($v['reorder_qty']);
        }
        if ($attrs !== []) {
            $level ??= StockLevel::firstOrCreate(['part_id' => $part->id, 'branch_id' => $branch->id], ['qty_on_hand' => 0, 'average_cost' => 0]);
            $level->forceFill($attrs)->save();
        }

        // Preferred supplier + a cost on their active price list.
        if (($v['supplier'] ?? '') !== '') {
            $supplier = Supplier::where('name', $v['supplier'])->first()
                ?? Supplier::create(['supplier_number' => $sequences->next('supplier'), 'name' => $v['supplier'], 'type' => 'local', 'is_active' => true]);
            ApprovedSupplier::where('part_id', $part->id)->where('supplier_id', '!=', $supplier->id)->update(['is_preferred' => false]);
            ApprovedSupplier::updateOrCreate(['part_id' => $part->id, 'supplier_id' => $supplier->id], ['is_preferred' => true]);
            if ($cost > 0) {
                $list = SupplierPriceList::firstOrCreate(['supplier_id' => $supplier->id, 'status' => 'active'],
                    ['name' => 'Uploaded costs', 'effective_date' => now()->toDateString()]);
                $list->items()->updateOrCreate(['part_id' => $part->id], ['supplier_part_number' => $part->part_number, 'description' => $part->description, 'cost_price' => $cost]);
            }
        }

        return $outcome;
    }

    private function persistPart(array $v): string
    {
        if (($v['part_number'] ?? '') === '') {
            throw new \RuntimeException('Part number is empty.');
        }

        $existing = Part::where('part_number', $v['part_number'])->first();
        $brandId = ($v['brand'] ?? '') !== '' ? $this->brandId($v['brand']) : null;
        $category = ($v['category'] ?? '') !== '' ? PartCategory::where('name', $v['category'])->first() : null;
        $unit = ($v['unit'] ?? '') !== ''
            ? UnitOfMeasure::where('name', $v['unit'])->orWhere('abbreviation', $v['unit'])->first()
            : null;

        if ($existing) {
            $existing->update(array_filter([
                'description' => $v['description'] ?: null,
                'oem_number' => $v['oem_number'] ?: null,
                'brand_id' => $brandId,
                'category_id' => $category?->id,
                'unit_id' => $unit?->id,
            ], fn ($x) => $x !== null));

            return 'updated';
        }

        if (($v['description'] ?? '') === '') {
            throw new \RuntimeException("{$v['part_number']}: a new part needs a description.");
        }
        $category ??= PartCategory::orderBy('sort_order')->first();
        $unit ??= UnitOfMeasure::where('name', 'Each')->orWhere('abbreviation', 'ea')->first() ?? UnitOfMeasure::orderBy('id')->first();
        if (! $category || ! $unit) {
            throw new \RuntimeException('No category/unit available — seed reference data first.');
        }

        Part::create([
            'part_number' => $v['part_number'],
            'description' => $v['description'],
            'oem_number' => $v['oem_number'] ?: null,
            'category_id' => $category->id,
            'brand_id' => $brandId,
            'unit_id' => $unit->id,
            'is_active' => true,
        ]);

        return 'created';
    }

    private function persistSupplier(array $v, NumberSequenceService $sequences): string
    {
        if (($v['name'] ?? '') === '') {
            throw new \RuntimeException('Supplier name is empty.');
        }
        $type = in_array(strtolower($v['type'] ?? ''), ['local', 'import', 'manufacturer'], true) ? strtolower($v['type']) : 'local';
        $attrs = [
            'name' => $v['name'], 'type' => $type,
            'phone' => $v['phone'] ?: null, 'email' => $v['email'] ?: null, 'city' => $v['city'] ?: null,
            'lead_time_days' => (int) $this->num($v['lead_time_days'] ?? null),
            'payment_terms_days' => (int) $this->num($v['payment_terms_days'] ?? null),
            'is_active' => true,
        ];

        $existing = ($v['supplier_number'] ?? '') !== ''
            ? Supplier::where('supplier_number', $v['supplier_number'])->first()
            : Supplier::where('name', $v['name'])->first();
        if ($existing) {
            $existing->update($attrs);

            return 'updated';
        }
        $attrs['supplier_number'] = ($v['supplier_number'] ?? '') !== '' ? $v['supplier_number'] : $sequences->next('supplier');
        Supplier::create($attrs);

        return 'created';
    }

    private function persistCustomer(array $v, NumberSequenceService $sequences): string
    {
        if (($v['name'] ?? '') === '') {
            throw new \RuntimeException('Customer name is empty.');
        }
        $type = in_array(strtolower($v['type'] ?? ''), ['individual', 'business', 'fleet', 'dealer'], true) ? strtolower($v['type']) : 'individual';
        $attrs = [
            'name' => $v['name'], 'type' => $type,
            'phone' => $v['phone'] ?: null, 'email' => $v['email'] ?: null, 'city' => $v['city'] ?: null,
            'credit_limit' => $this->num($v['credit_limit'] ?? null),
            'payment_terms_days' => (int) $this->num($v['payment_terms_days'] ?? null),
            'is_active' => true,
        ];

        $existing = ($v['customer_number'] ?? '') !== ''
            ? Customer::where('customer_number', $v['customer_number'])->first()
            : Customer::where('name', $v['name'])->where('is_walk_in', false)->first();
        if ($existing) {
            $existing->update($attrs);

            return 'updated';
        }
        $attrs['customer_number'] = ($v['customer_number'] ?? '') !== '' ? $v['customer_number'] : $sequences->next('customer');
        Customer::create($attrs);

        return 'created';
    }

    // ── Helpers ──────────────────────────────────────────────────────────

    /** Find a brand by name, or create it with a short unique code (brands need one). */
    private function brandId(string $name): int
    {
        $brand = PartBrand::where('name', $name)->first();
        if ($brand) {
            return $brand->id;
        }
        $base = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $name), 0, 8)) ?: 'BRAND';
        $code = $base;
        for ($n = 2; PartBrand::where('code', $code)->exists(); $n++) {
            $code = substr($base, 0, 6).$n;
        }

        return PartBrand::create(['name' => $name, 'code' => $code])->id;
    }

    private function num(?string $raw): float
    {
        return (float) preg_replace('/[^0-9.\-]/', '', str_replace(',', '', (string) $raw));
    }

    private function branch(): Branch
    {
        return Branch::where('is_main_branch', true)->first() ?? Branch::firstOrFail();
    }

    private function config(string $entity): array
    {
        if (! in_array($entity, self::ENTITIES, true)) {
            abort(404);
        }

        return $this->entities()[$entity];
    }

    private function meta(string $entity): array
    {
        $config = $this->config($entity);

        return [
            'entity' => $entity,
            'label' => $config['label'],
            'blurb' => $config['blurb'],
            'listRoute' => $config['listRoute'],
            'fields' => collect($config['fields'])->map(fn ($d, $k) => [
                'key' => $k, 'label' => $d['label'], 'required' => $d['required'] ?? false,
            ])->values(),
        ];
    }
}
