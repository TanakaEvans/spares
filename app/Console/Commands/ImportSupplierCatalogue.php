<?php

namespace App\Console\Commands;

use App\Exceptions\CatalogueSetupMissingException;
use App\Models\DataImport;
use App\Services\CsvImportService;
use App\Services\SupplierCatalogueImportService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Console twin of Data Upload › Supplier Catalogue, for large listings and
 * dry runs. Same service, same upload log entry as the web wizard.
 */
class ImportSupplierCatalogue extends Command
{
    protected $signature = 'catalogue:import
        {file : Path to the supplier listing (.xlsx, .xls or .csv)}
        {--supplier= : Supplier name (created if new; default "'.SupplierCatalogueImportService::DEFAULT_SUPPLIER.'")}
        {--discontinue-missing : The file is the supplier\'s FULL list — flag their parts not in it as discontinued}
        {--dry-run : Run everything, report, then roll back}';

    protected $description = 'Load a wholesaler product listing as catalogue parts linked to that supplier';

    public function handle(CsvImportService $csv): int
    {
        $source = (string) $this->argument('file');
        if (! is_file($source)) {
            $this->error("File not found: {$source}");

            return self::FAILURE;
        }

        $path = $csv->storeFile($source);
        $file = storage_path('app/private/'.$path);
        [$header] = $csv->parse($file, 0);

        $map = [];
        foreach (SupplierCatalogueImportService::FIELDS as $key => $def) {
            $map[$key] = $csv->guess($header, $def['keywords'], $def['label'], $key);
            $this->line(sprintf('  %-15s ← %s', $def['label'], $map[$key] === null ? '(not in file)' : $header[$map[$key]]));
            if (($def['required'] ?? false) && $map[$key] === null) {
                $this->error("Required column not found: {$def['label']}");
                @unlink($file);

                return self::FAILURE;
            }
        }

        $dry = (bool) $this->option('dry-run');
        DB::beginTransaction();
        try {
            $catalogue = app(SupplierCatalogueImportService::class)->begin((string) $this->option('supplier'));
            $started = microtime(true);
            $result = $csv->import($file, $map, fn (array $v) => $catalogue->persist($v));
            $discontinued = $this->option('discontinue-missing') ? $catalogue->syncDiscontinued() : null;

            if (! $dry) {
                DataImport::create([
                    'entity' => 'catalogue', 'file_name' => basename($source),
                    'created_count' => $result['created'], 'updated_count' => $result['updated'],
                    'skipped_count' => $result['skipped'], 'errors' => $result['errors'],
                ]);
            }
            $dry ? DB::rollBack() : DB::commit();
        } catch (CatalogueSetupMissingException $e) {
            DB::rollBack();
            $this->error($e->userMessage());

            return self::FAILURE;
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        } finally {
            @unlink($file);
        }

        $this->newLine();
        $this->info(($dry ? '[DRY RUN — rolled back] ' : '')."Supplier: {$catalogue->supplier()->name}");
        $this->line(sprintf('Created %d · updated %d · skipped %d (%d placeholder rows) · %d vehicle fitments linked in %.1fs',
            $result['created'], $result['updated'], $result['skipped'], $catalogue->placeholders, $catalogue->linked, microtime(true) - $started));
        if ($discontinued) {
            $this->line("Discontinued {$discontinued[0]} no-longer-listed parts · re-listed {$discontinued[1]}");
        }
        if ($catalogue->unclassified !== []) {
            $this->warn('Unmapped supplier categories (filed under Unclassified — add them to database/data/catalogue_category_map.csv):');
            foreach ($catalogue->unclassified as $name => $n) {
                $this->line("  {$n} × {$name}");
            }
        }
        foreach (array_slice($result['errors'], 0, 15) as $e) {
            $this->line("  row {$e['row']}: {$e['message']}");
        }

        return self::SUCCESS;
    }
}
