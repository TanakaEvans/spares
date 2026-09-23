<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Part;
use App\Models\PartBrand;
use App\Models\PartCategory;
use App\Models\Supplier;
use App\Models\UnitOfMeasure;
use App\Services\CsvImportService;
use App\Services\NumberSequenceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Master-data CSV import wizard: upload → map columns → commit. One reusable
 * flow for customers, suppliers and parts. Rows are matched on their natural
 * key (updateOrCreate) so re-importing a file corrects rather than duplicates.
 */
class DataImportController extends Controller
{
    /** Per-entity field definitions used for column-guessing and mapping. */
    private function entities(): array
    {
        return [
            'customers' => [
                'label' => 'Customers',
                'listRoute' => 'customers.index',
                'exportRoute' => 'customers.export',
                'fields' => [
                    'name' => ['label' => 'Name', 'required' => true, 'keywords' => ['name', 'customer', 'company']],
                    'customer_number' => ['label' => 'Customer # (blank = auto)', 'required' => false, 'keywords' => ['number', 'account', 'code']],
                    'type' => ['label' => 'Type', 'required' => false, 'keywords' => ['type']],
                    'phone' => ['label' => 'Phone', 'required' => false, 'keywords' => ['phone', 'tel', 'mobile', 'cell']],
                    'email' => ['label' => 'Email', 'required' => false, 'keywords' => ['email', 'mail']],
                    'city' => ['label' => 'City', 'required' => false, 'keywords' => ['city', 'town']],
                    'credit_limit' => ['label' => 'Credit limit', 'required' => false, 'keywords' => ['credit', 'limit']],
                    'payment_terms_days' => ['label' => 'Terms (days)', 'required' => false, 'keywords' => ['terms', 'days']],
                ],
            ],
            'suppliers' => [
                'label' => 'Suppliers',
                'listRoute' => 'suppliers.index',
                'exportRoute' => 'suppliers.export',
                'fields' => [
                    'name' => ['label' => 'Name', 'required' => true, 'keywords' => ['name', 'supplier', 'company']],
                    'supplier_number' => ['label' => 'Supplier # (blank = auto)', 'required' => false, 'keywords' => ['number', 'account', 'code']],
                    'type' => ['label' => 'Type', 'required' => false, 'keywords' => ['type']],
                    'phone' => ['label' => 'Phone', 'required' => false, 'keywords' => ['phone', 'tel']],
                    'email' => ['label' => 'Email', 'required' => false, 'keywords' => ['email', 'mail']],
                    'city' => ['label' => 'City', 'required' => false, 'keywords' => ['city', 'town']],
                    'lead_time_days' => ['label' => 'Lead time (days)', 'required' => false, 'keywords' => ['lead']],
                    'payment_terms_days' => ['label' => 'Terms (days)', 'required' => false, 'keywords' => ['terms', 'days']],
                ],
            ],
            'parts' => [
                'label' => 'Parts',
                'listRoute' => 'inventory.parts.index',
                'exportRoute' => 'inventory.parts.export',
                'fields' => [
                    'part_number' => ['label' => 'Part #', 'required' => true, 'keywords' => ['part', 'number', 'sku', 'code']],
                    'description' => ['label' => 'Description', 'required' => true, 'keywords' => ['desc', 'name']],
                    'oem_number' => ['label' => 'OEM #', 'required' => false, 'keywords' => ['oem', 'genuine']],
                    'category' => ['label' => 'Category (name)', 'required' => false, 'keywords' => ['categ', 'group']],
                    'brand' => ['label' => 'Brand (name)', 'required' => false, 'keywords' => ['brand', 'make', 'manufact']],
                    'unit' => ['label' => 'Unit (name/abbr)', 'required' => false, 'keywords' => ['unit', 'uom', 'measure']],
                ],
            ],
        ];
    }

    public function show(string $entity): Response
    {
        return Inertia::render('Imports/Wizard', $this->meta($entity));
    }

    /** Step 1: parse the uploaded CSV and return a preview + column guesses. */
    public function preview(Request $request, string $entity, CsvImportService $csv): Response
    {
        $config = $this->config($entity);
        $request->validate(['file' => ['required', 'file', 'mimes:csv,txt', 'max:10240']]);

        $path = $request->file('file')->store('imports/data');
        [$header, $rows] = $csv->parse(storage_path('app/private/'.$path));

        $guess = [];
        foreach ($config['fields'] as $key => $def) {
            $guess[$key] = $csv->guess($header, $def['keywords']);
        }

        return Inertia::render('Imports/Wizard', array_merge($this->meta($entity), [
            'preview' => ['path' => $path, 'header' => $header, 'rows' => $rows, 'guess' => $guess],
        ]));
    }

    /** Step 2: commit the mapped rows. */
    public function store(Request $request, string $entity, CsvImportService $csv, NumberSequenceService $sequences): RedirectResponse
    {
        $config = $this->config($entity);
        $data = $request->validate([
            'path' => ['required', 'string'],
            'map' => ['required', 'array'],
        ]);

        // Required fields must be mapped.
        foreach ($config['fields'] as $key => $def) {
            if (($def['required'] ?? false) && ! is_numeric($data['map'][$key] ?? null)) {
                return back()->withErrors(['map' => "Map the required column: {$def['label']}."]);
            }
        }

        $file = storage_path('app/private/'.$data['path']);
        abort_unless(is_file($file) && str_contains($data['path'], 'imports/data'), 404);

        $persist = match ($entity) {
            'customers' => fn (array $v) => $this->persistCustomer($v, $sequences),
            'suppliers' => fn (array $v) => $this->persistSupplier($v, $sequences),
            'parts' => fn (array $v) => $this->persistPart($v),
        };

        $result = $csv->import($file, $data['map'], $persist);
        @unlink($file);

        return redirect()->route($config['listRoute'])
            ->with('success', sprintf('%s import: %d created, %d updated, %d skipped.',
                $config['label'], $result['created'], $result['updated'], $result['skipped']))
            ->with('import_errors', $result['errors']);
    }

    // ── Persisters ───────────────────────────────────────────────────────

    private function persistCustomer(array $v, NumberSequenceService $sequences): string
    {
        if (($v['name'] ?? '') === '') {
            throw new \RuntimeException('Name is empty.');
        }
        $type = in_array($v['type'] ?? '', ['individual', 'business', 'fleet', 'dealer', 'cash'], true) ? $v['type'] : 'individual';
        $attrs = [
            'name' => $v['name'], 'type' => $type,
            'phone' => $v['phone'] ?: null, 'email' => $v['email'] ?: null, 'city' => $v['city'] ?: null,
            'credit_limit' => $this->num($v['credit_limit'] ?? null),
            'payment_terms_days' => (int) $this->num($v['payment_terms_days'] ?? null),
        ];

        if (($v['customer_number'] ?? '') !== '') {
            $existing = Customer::where('customer_number', $v['customer_number'])->first();
            if ($existing) {
                $existing->update($attrs);

                return 'updated';
            }
            $attrs['customer_number'] = $v['customer_number'];
        } else {
            $attrs['customer_number'] = $sequences->next('customer');
        }
        Customer::create($attrs);

        return 'created';
    }

    private function persistSupplier(array $v, NumberSequenceService $sequences): string
    {
        if (($v['name'] ?? '') === '') {
            throw new \RuntimeException('Name is empty.');
        }
        $type = in_array($v['type'] ?? '', ['local', 'import', 'manufacturer'], true) ? $v['type'] : 'local';
        $attrs = [
            'name' => $v['name'], 'type' => $type,
            'phone' => $v['phone'] ?: null, 'email' => $v['email'] ?: null, 'city' => $v['city'] ?: null,
            'lead_time_days' => (int) $this->num($v['lead_time_days'] ?? null),
            'payment_terms_days' => (int) $this->num($v['payment_terms_days'] ?? null),
        ];

        if (($v['supplier_number'] ?? '') !== '') {
            $existing = Supplier::where('supplier_number', $v['supplier_number'])->first();
            if ($existing) {
                $existing->update($attrs);

                return 'updated';
            }
            $attrs['supplier_number'] = $v['supplier_number'];
        } else {
            $attrs['supplier_number'] = $sequences->next('supplier');
        }
        Supplier::create($attrs);

        return 'created';
    }

    private function persistPart(array $v): string
    {
        if (($v['part_number'] ?? '') === '') {
            throw new \RuntimeException('Part number is empty.');
        }

        $existing = Part::where('part_number', $v['part_number'])->first();
        $brandId = ($v['brand'] ?? '') !== '' ? PartBrand::firstOrCreate(['name' => $v['brand']])->id : null;

        if ($existing) {
            $existing->update(array_filter([
                'description' => $v['description'] ?: null,
                'oem_number' => $v['oem_number'] ?: null,
                'brand_id' => $brandId,
            ], fn ($x) => $x !== null));

            return 'updated';
        }

        if (($v['description'] ?? '') === '') {
            throw new \RuntimeException('New part needs a description.');
        }
        $category = ($v['category'] ?? '') !== '' ? PartCategory::where('name', $v['category'])->first() : null;
        $category ??= PartCategory::orderBy('id')->first();
        $unit = ($v['unit'] ?? '') !== ''
            ? UnitOfMeasure::where('name', $v['unit'])->orWhere('abbreviation', $v['unit'])->first()
            : null;
        $unit ??= UnitOfMeasure::orderBy('id')->first();

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

    // ── Helpers ──────────────────────────────────────────────────────────

    private function num(?string $raw): float
    {
        return (float) str_replace([',', ' ', '$'], '', (string) $raw);
    }

    private function config(string $entity): array
    {
        return $this->entities()[$entity] ?? abort(404);
    }

    private function meta(string $entity): array
    {
        $config = $this->config($entity);

        return [
            'entity' => $entity,
            'label' => $config['label'],
            'listRoute' => $config['listRoute'],
            'exportRoute' => $config['exportRoute'],
            'fields' => collect($config['fields'])->map(fn ($d, $k) => [
                'key' => $k, 'label' => $d['label'], 'required' => $d['required'] ?? false,
            ])->values(),
        ];
    }
}
