<?php

namespace App\Http\Controllers\Suppliers;

use App\Http\Controllers\Controller;
use App\Models\Part;
use App\Models\PartCrossReference;
use App\Models\Supplier;
use App\Models\SupplierPriceList;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

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
        return Inertia::render('Suppliers/PriceListImport', [
            'supplier' => $supplier->only(['id', 'name', 'supplier_number']),
        ]);
    }

    /** Step 1: parse the uploaded CSV and return a preview for column mapping. */
    public function preview(Request $request, Supplier $supplier): Response
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:5120'],
        ]);

        $path = $request->file('file')->store('imports/price_lists');
        [$header, $rows] = $this->parseCsv(storage_path('app/private/'.$path), 8);

        return Inertia::render('Suppliers/PriceListImport', [
            'supplier' => $supplier->only(['id', 'name', 'supplier_number']),
            'preview' => [
                'path' => $path,
                'header' => $header,
                'rows' => $rows,
                'guess' => [
                    'supplier_part_number' => $this->guessColumn($header, ['part', 'number', 'code', 'sku']),
                    'description' => $this->guessColumn($header, ['desc', 'name']),
                    'cost_price' => $this->guessColumn($header, ['price', 'cost']),
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
            'map' => ['required', 'array'],
            'map.supplier_part_number' => ['required', 'integer', 'min:0'],
            'map.cost_price' => ['required', 'integer', 'min:0', 'different:map.supplier_part_number'],
            'map.description' => ['nullable', 'integer', 'min:0'],
            'activate' => ['sometimes', 'boolean'],
        ]);

        $file = storage_path('app/private/'.$data['path']);
        abort_unless(is_file($file) && str_contains($data['path'], 'imports/price_lists'), 404);

        [, $rows] = $this->parseCsv($file, PHP_INT_MAX);

        $priceList = DB::transaction(function () use ($supplier, $data, $rows, $request) {
            $priceList = SupplierPriceList::create([
                'supplier_id' => $supplier->id,
                'name' => $data['name'],
                'effective_date' => now()->toDateString(),
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

                $priceList->items()->create([
                    'part_id' => $part?->id,
                    'supplier_part_number' => $number,
                    'description' => isset($data['map']['description'])
                        ? trim((string) ($row[$data['map']['description']] ?? '')) ?: null
                        : null,
                    'cost_price' => $price,
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
