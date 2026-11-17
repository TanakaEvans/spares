<?php

namespace App\Console\Commands;

use App\Models\DataImport;
use App\Models\Part;
use App\Models\PartCategory;
use App\Models\UnitOfMeasure;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Load a simple parts list (part number + vehicle application) into the
 * catalogue — e.g. the "BREAK PADS" / "TIE ROD ENDS" sheets, which carry a
 * part number, vehicle, capacity, year and part name but no cost or quantity.
 *
 * Each row becomes a searchable part with a composed description like
 * "BRAKE PADS FRONT - FIESTA/MAZDA 1997". No cost/stock is set: once these are
 * bought in (a GRN or opening-stock upload records a cost) the POS prices them
 * automatically at cost + markup. Idempotent on part number; existing parts
 * keep their description (only blanks are filled) so a re-run never clobbers a
 * better catalogue entry.
 */
class ImportPartsList extends Command
{
    protected $signature = 'spares:import-parts-list
        {file : Path to the parts list (.xlsx, .xls or .csv)}
        {--category= : Category name for every part (created if new; default derived from the part name)}
        {--dry-run : Read and report, change nothing}';

    protected $description = 'Load a vehicle parts list (part number + application) into the catalogue';

    public function handle(): int
    {
        $source = (string) $this->argument('file');
        if (! is_file($source)) {
            $this->error("File not found: {$source}");

            return self::FAILURE;
        }

        $rows = $this->readRows($source);
        if ($rows === []) {
            $this->error('No usable rows. Expected a Part Number column plus vehicle/part-name columns.');

            return self::FAILURE;
        }

        $dry = (bool) $this->option('dry-run');
        $categoryName = trim((string) $this->option('category')) ?: $this->deriveCategory($rows);
        $unit = UnitOfMeasure::where('name', 'Each')->orWhere('abbreviation', 'ea')->first() ?? UnitOfMeasure::orderBy('id')->first();
        if (! $unit) {
            $this->error('No unit of measure available — seed reference data first (php artisan db:seed).');

            return self::FAILURE;
        }
        $category = $dry ? null : $this->category($categoryName);

        $this->info(sprintf('%d parts → category "%s"', count($rows), $categoryName));

        $created = 0;
        $updated = 0;
        $skipped = 0;
        $errors = [];
        $import = $dry ? null : DataImport::create(['entity' => 'parts', 'file_name' => basename($source)]);

        foreach ($rows as $r) {
            try {
                $existing = Part::where('part_number', $r['part_number'])->first();
                if ($dry) {
                    $existing ? $updated++ : $created++;

                    continue;
                }
                if ($existing) {
                    // Keep a real existing description; only fill a blank one. Always ensure a category.
                    $attrs = ['category_id' => $existing->category_id ?: $category->id, 'is_active' => true];
                    if (trim((string) $existing->description) === '' && $r['description'] !== '') {
                        $attrs['description'] = $r['description'];
                    }
                    $existing->update($attrs);
                    $updated++;
                } else {
                    Part::create([
                        'part_number' => $r['part_number'],
                        'description' => $r['description'] !== '' ? $r['description'] : $r['part_number'],
                        'category_id' => $category->id,
                        'unit_id' => $unit->id,
                        'is_active' => true,
                    ]);
                    $created++;
                }
            } catch (\Throwable $e) {
                $skipped++;
                if (count($errors) < 50) {
                    $errors[] = ['row' => $r['part_number'], 'message' => $e->getMessage()];
                }
            }
        }

        if (! $dry) {
            $import->update(['created_count' => $created, 'updated_count' => $updated, 'skipped_count' => $skipped, 'errors' => $errors]);
            $this->line("Created {$created} · updated {$updated} · skipped {$skipped}.");
        } else {
            $this->warn("[DRY RUN] Would create {$created} and update {$updated}. Nothing changed.");
        }
        foreach (array_slice($errors, 0, 15) as $e) {
            $this->line("  {$e['row']}: {$e['message']}");
        }

        return self::SUCCESS;
    }

    /** Read the sheet into [part_number, description, part_name] rows. */
    private function readRows(string $source): array
    {
        $reader = IOFactory::createReaderForFile($source);
        $reader->setReadDataOnly(true);
        $sheet = $reader->load($source)->getSheet(0);
        $table = $sheet->toArray(null, true, true, false);
        if ($table === []) {
            return [];
        }

        $header = array_map(fn ($c) => $this->norm($c), $table[0]);
        array_shift($table);

        // Column roles. The first column is the part code; the rest describe it.
        $codeCol = 0;
        $nameCol = $this->col($header, ['partname', 'partnme']);
        if ($nameCol === null) {
            // "BREAK PADS" files repeat the part name under a second "PART NUMBER" header.
            foreach ($header as $i => $h) {
                if ($i !== $codeCol && str_contains($h, 'partnumber')) {
                    $nameCol = $i;
                    break;
                }
            }
        }
        $posCol = ($nameCol !== null && isset($header[$nameCol + 1]) && $header[$nameCol + 1] === '') ? $nameCol + 1 : null;
        $vehicleCol = $this->col($header, ['vihicle', 'vehicle', 'vehic']);
        $capCol = $this->col($header, ['capacity', 'capac', 'engine']);
        $yearCol = $this->col($header, ['year']);

        $rows = [];
        foreach ($table as $r) {
            $code = trim((string) ($r[$codeCol] ?? ''));
            if ($code === '' || strtolower($code) === 'part number') {
                continue;
            }
            $type = trim(($nameCol !== null ? (string) ($r[$nameCol] ?? '') : '').' '.($posCol !== null ? (string) ($r[$posCol] ?? '') : ''));
            $app = trim(implode(' ', array_filter([
                $vehicleCol !== null ? (string) ($r[$vehicleCol] ?? '') : '',
                $capCol !== null ? (string) ($r[$capCol] ?? '') : '',
                $yearCol !== null ? (string) ($r[$yearCol] ?? '') : '',
            ], fn ($v) => trim($v) !== '')));
            $type = $this->tidy($type);
            $app = $this->tidy($app);
            $desc = $type !== '' && $app !== '' ? "{$type} - {$app}" : ($type ?: $app);

            $rows[] = ['part_number' => $code, 'description' => $desc, 'part_name' => $this->tidy($nameCol !== null ? (string) ($r[$nameCol] ?? '') : '')];
        }

        return $rows;
    }

    private function deriveCategory(array $rows): string
    {
        $names = array_filter(array_map(fn ($r) => $r['part_name'], $rows), fn ($n) => $n !== '');
        if ($names === []) {
            return 'Unclassified';
        }
        $counts = array_count_values($names);
        arsort($counts);

        return Str::title(strtolower((string) array_key_first($counts)));
    }

    private function category(string $name): PartCategory
    {
        $existing = PartCategory::whereRaw('lower(name) = ?', [strtolower($name)])->first();
        if ($existing) {
            return $existing;
        }
        $base = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $name), 0, 8)) ?: 'CAT';
        $code = $base;
        for ($n = 2; PartCategory::where('code', $code)->exists(); $n++) {
            $code = substr($base, 0, 6).$n;
        }

        return PartCategory::create(['name' => $name, 'code' => $code, 'is_active' => true, 'sort_order' => 99]);
    }

    private function col(array $header, array $candidates): ?int
    {
        foreach ($candidates as $c) {
            foreach ($header as $i => $name) {
                if ($name !== '' && str_contains($name, $c)) {
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

    /** Collapse whitespace and trim stray separators. */
    private function tidy(string $s): string
    {
        return trim(preg_replace('/\s+/', ' ', str_replace(['_'], ' ', $s)));
    }
}
