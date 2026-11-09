<?php

namespace App\Console\Commands;

use App\Models\ApprovedSupplier;
use App\Models\Branch;
use App\Models\DataImport;
use App\Models\Part;
use App\Models\PartCategory;
use App\Models\StockLevel;
use App\Models\Supplier;
use App\Models\SupplierPriceList;
use App\Models\UnitOfMeasure;
use App\Services\NumberSequenceService;
use App\Services\OpeningBalanceService;
use App\Services\StockLedgerService;
use Illuminate\Console\Command;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Load a Sparepro tax-invoice extract (the wholesaler's "Invoices Master
 * Extract" sheet) as real opening stock. Each row is an invoice line — part
 * number, description, quantity received and the VAT-exclusive unit cost.
 *
 * Lines are aggregated per part number (quantities summed, cost weighted-
 * averaged across invoices), so a part that appears on two invoices lands once
 * with the right total on hand. Stock is posted through StockLedgerService as
 * an OPENING_BALANCE movement (goods already on the shelf at go-live — no
 * supplier payable is created), the preferred supplier is linked with its cost,
 * and the opening inventory is journalised onto the GL so 1310 reconciles.
 *
 * Idempotent: a part that already carries stock at the branch is left untouched,
 * so re-running the same file never double-counts.
 */
class ImportSpareproInvoices extends Command
{
    protected $signature = 'spares:import-invoices
        {file : Path to the invoice extract (.xlsx, .xls or .csv)}
        {--supplier=Sparepro : Supplier name to link as preferred (created if new)}
        {--dry-run : Read and report, change nothing}';

    protected $description = 'Load a Sparepro invoice extract as opening stock (parts, quantities, costs, preferred supplier)';

    public function handle(StockLedgerService $ledger, OpeningBalanceService $opening, NumberSequenceService $sequences): int
    {
        $source = (string) $this->argument('file');
        if (! is_file($source)) {
            $this->error("File not found: {$source}");

            return self::FAILURE;
        }

        $rows = $this->readRows($source);
        if ($rows === []) {
            $this->error('No data rows found. Expected columns: Part Number, Description, Supp.Qty, Unit Price (ZAR), Nett Amount (ZAR).');

            return self::FAILURE;
        }

        $parts = $this->aggregate($rows);
        $this->info(sprintf('%d invoice lines → %d distinct parts · %s units · R%s cost (excl VAT)',
            count($rows), count($parts),
            number_format(array_sum(array_column($parts, 'qty')), 0),
            number_format(array_sum(array_map(fn ($p) => $p['qty'] * $p['cost'], $parts)), 2)));

        $dry = (bool) $this->option('dry-run');
        $branch = Branch::where('is_main_branch', true)->first() ?? Branch::firstOrFail();

        $supplier = $dry ? null : $this->supplier((string) $this->option('supplier'), $sequences);
        $category = PartCategory::where('name', 'Unclassified')->first() ?? PartCategory::orderBy('sort_order')->first();
        $unit = UnitOfMeasure::where('name', 'Each')->orWhere('abbreviation', 'ea')->first() ?? UnitOfMeasure::orderBy('id')->first();
        if (! $category || ! $unit) {
            $this->error('No category/unit available — seed reference data first (php artisan db:seed).');

            return self::FAILURE;
        }

        $import = $dry ? null : DataImport::create(['entity' => 'stock', 'file_name' => basename($source)]);
        $created = 0;
        $priced = 0;
        $stocked = 0;
        $skipped = 0;
        $errors = [];

        $bar = $this->output->createProgressBar(count($parts));
        foreach ($parts as $pn => $p) {
            try {
                $part = Part::where('part_number', $pn)->first();
                if (! $part) {
                    if ($dry) {
                        $created++;
                    } else {
                        $part = Part::create([
                            'part_number' => $pn,
                            'description' => $p['description'] !== '' ? $p['description'] : $pn,
                            'category_id' => $category->id,
                            'unit_id' => $unit->id,
                            'is_active' => true,
                        ]);
                        $created++;
                    }
                }

                if ($dry) {
                    $stocked++;
                    $bar->advance();

                    continue;
                }

                // Opening stock — once. A part already holding stock is left alone.
                $level = StockLevel::where('part_id', $part->id)->where('branch_id', $branch->id)->first();
                if ($p['qty'] > 0 && (! $level || (float) $level->qty_on_hand <= 0)) {
                    $ledger->post($part->id, $branch, 'OPENING_BALANCE', $p['qty'], $p['cost'],
                        'DataImport', $import->id, 'Sparepro invoice extract', null);
                    $stocked++;
                } else {
                    $skipped++;
                }

                // Preferred supplier + its cost on the active price list.
                ApprovedSupplier::where('part_id', $part->id)->where('supplier_id', '!=', $supplier->id)->update(['is_preferred' => false]);
                ApprovedSupplier::updateOrCreate(['part_id' => $part->id, 'supplier_id' => $supplier->id], ['is_preferred' => true]);
                $list = SupplierPriceList::firstOrCreate(['supplier_id' => $supplier->id, 'status' => 'active'],
                    ['name' => 'Sparepro costs', 'effective_date' => now()->toDateString()]);
                $list->items()->updateOrCreate(['part_id' => $part->id],
                    ['supplier_part_number' => $part->part_number, 'description' => $part->description, 'cost_price' => $p['cost']]);
                $priced++;
            } catch (\Throwable $e) {
                $skipped++;
                if (count($errors) < 50) {
                    $errors[] = ['row' => $pn, 'message' => $e->getMessage()];
                }
            }
            $bar->advance();
        }
        $bar->finish();
        $this->newLine(2);

        if (! $dry) {
            $journal = $opening->postOpeningInventory();
            $import->update(['created_count' => $created, 'updated_count' => $priced, 'skipped_count' => $skipped, 'errors' => $errors]);

            $this->info("Supplier: {$supplier->name}");
            $this->line("Parts created: {$created} · stock posted: {$stocked} · already stocked (skipped): {$skipped} · supplier-linked: {$priced}");
            if ($journal) {
                $this->line('Opening inventory journalised: DR 1310 / CR 3300 R'.number_format($opening->stockValue(), 2));
            }
            $integrity = $ledger->verifyIntegrity($branch->id);
            $this->line($integrity->isEmpty()
                ? 'Stock integrity: clean (stock levels match the ledger).'
                : "Stock integrity: {$integrity->count()} mismatch(es) — investigate.");
        } else {
            $this->warn("[DRY RUN] Would create {$created} parts and post stock for {$stocked}. Nothing changed.");
        }

        foreach (array_slice($errors, 0, 15) as $e) {
            $this->line("  {$e['row']}: {$e['message']}");
        }

        return self::SUCCESS;
    }

    /** Read the sheet into [part_number, description, qty, unit_cost] rows. */
    private function readRows(string $source): array
    {
        $reader = IOFactory::createReaderForFile($source);
        $reader->setReadDataOnly(true);
        $sheet = $reader->load($source)->getSheet(0);
        $table = $sheet->toArray(null, true, true, false);
        if ($table === []) {
            return [];
        }

        $header = array_map(fn ($c) => $this->norm($c), array_shift($table));
        $col = fn (array $names) => $this->findCol($header, $names);
        $iPn = $col(['partnumber', 'partno', 'part']);
        $iDesc = $col(['description', 'desc']);
        $iQty = $col(['suppqty', 'supplyqty', 'qty', 'quantity']);
        $iUnit = $col(['unitprice', 'unitpricezar', 'price', 'cost']);
        $iNett = $col(['nettamount', 'nettamountzar', 'nett', 'net', 'amount']);

        if ($iPn === null || $iQty === null || ($iUnit === null && $iNett === null)) {
            return [];
        }

        $rows = [];
        foreach ($table as $r) {
            $pn = trim((string) ($r[$iPn] ?? ''));
            if ($pn === '') {
                continue;
            }
            $qty = $this->num($r[$iQty] ?? null);
            $nett = $iNett !== null ? $this->num($r[$iNett] ?? null) : null;
            $unit = $iUnit !== null ? $this->num($r[$iUnit] ?? null) : null;
            // Prefer the line total (nett) for weighted-average cost; fall back to unit × qty.
            $value = $nett !== null && $nett > 0 ? $nett : ($unit ?? 0) * $qty;
            $rows[] = [
                'pn' => $pn,
                'description' => trim((string) ($iDesc !== null ? ($r[$iDesc] ?? '') : '')),
                'qty' => $qty,
                'value' => $value,
            ];
        }

        return $rows;
    }

    /** Collapse invoice lines to one row per part: summed qty, weighted-average cost. */
    private function aggregate(array $rows): array
    {
        $agg = [];
        foreach ($rows as $r) {
            $a = &$agg[$r['pn']];
            $a['qty'] = ($a['qty'] ?? 0) + $r['qty'];
            $a['value'] = ($a['value'] ?? 0) + $r['value'];
            if (($r['description'] ?? '') !== '') {
                $a['description'] = $r['description']; // last non-empty wins
            }
            $a['description'] ??= '';
            unset($a);
        }
        foreach ($agg as &$a) {
            $a['cost'] = $a['qty'] > 0 ? round($a['value'] / $a['qty'], 4) : 0.0;
        }

        return $agg;
    }

    private function supplier(string $name, NumberSequenceService $sequences): Supplier
    {
        return Supplier::where('name', $name)->first()
            ?? Supplier::create([
                'supplier_number' => $sequences->next('supplier'),
                'name' => $name,
                'type' => 'local',
                'is_active' => true,
            ]);
    }

    private function findCol(array $header, array $candidates): ?int
    {
        foreach ($candidates as $c) {
            foreach ($header as $i => $name) {
                if ($name === $c || str_contains($name, $c)) {
                    return $i;
                }
            }
        }

        return null;
    }

    private function norm($s): string
    {
        return preg_replace('/[^a-z0-9]+/', '', strtolower((string) $s));
    }

    private function num($raw): float
    {
        return (float) preg_replace('/[^0-9.\-]/', '', str_replace(',', '', (string) $raw));
    }
}
