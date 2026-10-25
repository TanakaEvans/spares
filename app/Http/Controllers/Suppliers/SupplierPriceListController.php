<?php

namespace App\Http\Controllers\Suppliers;

use App\Http\Controllers\Controller;
use App\Models\ApprovedSupplier;
use App\Models\Part;
use App\Models\PartCategory;
use App\Models\PartCrossReference;
use App\Models\Supplier;
use App\Models\SupplierPriceList;
use App\Services\CsvImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Price-list import wizard (Module 6.2 / local-data-seeding):
 * upload CSV → preview + column map → import with part matching
 * (part_number → oem_number → cross-references) → activate
 * (one active list per supplier).
 */
class SupplierPriceListController extends Controller
{
    public function import(Supplier $supplier): Response
    {
        $activeList = SupplierPriceList::where('supplier_id', $supplier->id)->where('status', 'active')->first();

        return Inertia::render('Suppliers/PriceListImport', [
            'supplier' => $supplier->only(['id', 'name', 'supplier_number']),
            'supplierPartCount' => ApprovedSupplier::where('supplier_id', $supplier->id)->count(),
            'activeList' => $activeList?->only(['id', 'name', 'effective_date']),
            'categories' => PartCategory::whereNull('parent_id')->orderBy('sort_order')->get(['id', 'name', 'code'])
                ->map(fn ($c) => ['id' => $c->id, 'label' => "{$c->name} ({$c->code})"]),
        ]);
    }

    /**
     * Step 0: a pre-filled template — the chosen part list with their codes,
     * descriptions and current cost; only the New Cost column is left blank.
     * scope=supplier_parts (default) | category | all.
     */
    public function template(Request $request, Supplier $supplier): StreamedResponse
    {
        $scope = $request->string('scope')->toString() ?: 'supplier_parts';
        $categoryId = (int) $request->input('category_id', 0);

        $current = SupplierPriceList::where('supplier_id', $supplier->id)->where('status', 'active')->first();
        $currentPrices = $current
            ? $current->items()->pluck('cost_price', 'part_id')->all()
            : [];

        $query = Part::with('category:id,name')->where('is_active', true)->orderBy('part_number');
        match ($scope) {
            'category' => $query->when($categoryId > 0, fn ($q) => $q->where('category_id', $categoryId)),
            'all' => null,
            default => $query->whereIn('id', ApprovedSupplier::where('supplier_id', $supplier->id)->select('part_id')),
        };
        // A category scope with no pick falls back to the supplier's own parts.
        if ($scope === 'category' && $categoryId <= 0) {
            $query->whereIn('id', ApprovedSupplier::where('supplier_id', $supplier->id)->select('part_id'));
        }

        $rows = $query->limit(20000)->get(['id', 'part_number', 'oem_number', 'description', 'category_id', 'unit_id']);
        $units = \App\Models\UnitOfMeasure::pluck('abbreviation', 'id');
        $theirCodes = ApprovedSupplier::where('supplier_id', $supplier->id)->pluck('supplier_part_number', 'part_id');

        $book = new Spreadsheet;
        $sheet = $book->getActiveSheet();
        $sheet->setTitle('Price list');
        $sheet->fromArray(['Part Number', 'Description', 'Category', 'UOM', 'Current Cost', 'New Cost'], null, 'A1');
        $sheet->getStyle('A1:F1')->getFont()->setBold(true);
        $sheet->getStyle('F')->getNumberFormat()->setFormatCode('0.00');
        $sheet->getStyle('E')->getNumberFormat()->setFormatCode('0.00');
        $sheet->freezePane('A2');
        $sheet->setAutoFilter('A1:F1');
        foreach (['A' => 16, 'B' => 50, 'C' => 22, 'D' => 8, 'E' => 14, 'F' => 14] as $col => $w) {
            $sheet->getColumnDimension($col)->setWidth($w);
        }

        $r = 2;
        foreach ($rows as $p) {
            $sheet->setCellValue("A{$r}", $theirCodes[$p->id] ?? $p->oem_number ?? $p->part_number);
            $sheet->setCellValue("B{$r}", $p->description);
            $sheet->setCellValue("C{$r}", $p->category?->name);
            $sheet->setCellValue("D{$r}", $units[$p->unit_id] ?? 'ea');
            if (isset($currentPrices[$p->id])) {
                $sheet->setCellValue("E{$r}", (float) $currentPrices[$p->id]);
            }
            $r++;
        }

        $name = 'price-list-template-'.strtolower(preg_replace('/[^a-z0-9]+/i', '-', $supplier->name)).'.xlsx';

        return response()->streamDownload(function () use ($book) {
            (new Xlsx($book))->save('php://output');
        }, $name, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }

    /** Step 1: parse the uploaded CSV/Excel and return a preview for column mapping. */
    public function preview(Request $request, Supplier $supplier, CsvImportService $csv): Response
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt,xlsx,xls', 'max:5120'],
        ]);

        $path = $csv->storeFile($request->file('file')->getRealPath(), $request->file('file')->getClientOriginalExtension());
        $file = storage_path('app/private/'.$path);
        [$header, $rows] = $csv->parse($file, 8);

        // Which cells already resolve to a catalogue part — the map step
        // lights the column that matches instead of guessing blind.
        $matches = [];
        foreach ($rows as $r => $row) {
            foreach ($row as $c => $cell) {
                $cell = trim((string) $cell);
                if ($cell === '' || strlen($cell) > 60) {
                    continue;
                }
                if ($part = $this->matchPart($cell)) {
                    $matches["{$r}.{$c}"] = ['part_number' => $part->part_number, 'description' => $part->description];
                }
            }
        }

        return Inertia::render('Suppliers/PriceListImport', [
            'supplier' => $supplier->only(['id', 'name', 'supplier_number']),
            'preview' => [
                'path' => $path,
                'header' => $header,
                'rows' => $rows,
                'matches' => $matches,
                'guess' => [
                    'supplier_part_number' => $this->guessColumn($header, ['part', 'number', 'code', 'sku']),
                    'description' => $this->guessColumn($header, ['desc', 'name']),
                    'cost_price' => $this->guessColumn($header, ['new cost', 'price', 'cost']),
                    'minimum_qty' => $this->guessColumn($header, ['min', 'moq', 'qty']),
                ],
            ],
        ]);
    }

    /** Step 2: import with the confirmed column mapping. */
    public function store(Request $request, Supplier $supplier): RedirectResponse
    {
        $data = $request->validate([
            'path' => ['required', 'string'],
            'name' => ['required', 'string', 'max:100'],
            'effective_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:500'],
            'map' => ['required', 'array'],
            'map.supplier_part_number' => ['required', 'integer', 'min:0'],
            'map.cost_price' => ['required', 'integer', 'min:0', 'different:map.supplier_part_number'],
            'map.description' => ['nullable', 'integer', 'min:0'],
            'map.minimum_qty' => ['nullable', 'integer', 'min:0'],
            'activate' => ['sometimes', 'boolean'],
        ]);

        $file = storage_path('app/private/'.$data['path']);
        abort_unless(is_file($file) && str_contains($data['path'], 'imports/price_lists'), 404);

        [, $rows] = $this->parseCsv($file, PHP_INT_MAX);

        $priceList = DB::transaction(function () use ($supplier, $data, $rows, $request) {
            $priceList = SupplierPriceList::create([
                'supplier_id' => $supplier->id,
                'name' => $data['name'],
                'effective_date' => $data['effective_date'] ?: now()->toDateString(),
                'notes' => $data['notes'] ?? null,
                'status' => 'pending',
                'imported_by' => $request->user()->id,
            ]);

            $matched = 0;
            $unmatched = 0;

            foreach ($rows as $row) {
                $number = trim((string) ($row[$data['map']['supplier_part_number']] ?? ''));
                $priceRaw = (string) ($row[$data['map']['cost_price']] ?? '');
                $price = (float) str_replace([',', ' ', '$'], '', $priceRaw);

                if ($number === '' || $price <= 0) {
                    continue;
                }

                $part = $this->matchPart($number);
                $part === null ? $unmatched++ : $matched++;

                $minQty = isset($data['map']['minimum_qty']) ? $this->num($row[$data['map']['minimum_qty']] ?? null) : 0;

                $priceList->items()->create([
                    'part_id' => $part?->id,
                    'supplier_part_number' => $number,
                    'description' => isset($data['map']['description'])
                        ? trim((string) ($row[$data['map']['description']] ?? '')) ?: null
                        : null,
                    'cost_price' => $price,
                    'minimum_qty' => $minQty > 0 ? $minQty : null,
                ]);
            }

            $priceList->update(['matched_count' => $matched, 'unmatched_count' => $unmatched]);

            if ($data['activate'] ?? false) {
                $this->doActivate($priceList);
            }

            return $priceList;
        });

        @unlink($file);

        return redirect()->route('suppliers.show', $supplier)->with('success', sprintf(
            'Price list "%s" imported: %d matched, %d unmatched rows%s.',
            $priceList->name,
            $priceList->matched_count,
            $priceList->unmatched_count,
            $priceList->status === 'active' ? ' — activated' : ''
        ));
    }

    public function activate(Supplier $supplier, SupplierPriceList $priceList): RedirectResponse
    {
        abort_unless($priceList->supplier_id === $supplier->id, 404);
        $this->doActivate($priceList);

        return back()->with('success', "Price list {$priceList->name} is now active.");
    }

    /** One active list per supplier — activating supersedes the previous (6.2 rule 1). */
    private function doActivate(SupplierPriceList $priceList): void
    {
        DB::transaction(function () use ($priceList) {
            SupplierPriceList::where('supplier_id', $priceList->supplier_id)
                ->where('status', 'active')
                ->update(['status' => 'superseded']);

            $priceList->update(['status' => 'active']);
        });
    }

    /** Match order: our part_number → oem_number → cross-references. */
    private function matchPart(string $number): ?Part
    {
        return Part::where('part_number', $number)->first()
            ?? Part::where('oem_number', $number)->first()
            ?? PartCrossReference::where('reference_number', $number)->first()?->part;
    }

    private function num(mixed $v): float
    {
        return (float) str_replace([',', ' ', 'R', '$'], '', (string) ($v ?? ''));
    }

    private function parseCsv(string $file, int $limit): array
    {
        $handle = fopen($file, 'r');
        $header = fgetcsv($handle) ?: [];
        $rows = [];

        while (count($rows) < $limit && ($row = fgetcsv($handle)) !== false) {
            $rows[] = $row;
        }

        fclose($handle);

        return [$header, $rows];
    }

    private function guessColumn(array $header, array $keywords): ?int
    {
        foreach ($header as $i => $name) {
            foreach ($keywords as $kw) {
                if (str_contains(mb_strtolower((string) $name), $kw)) {
                    return $i;
                }
            }
        }

        return null;
    }
}
