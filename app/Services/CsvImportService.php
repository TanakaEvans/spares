<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Upload engine behind the Data Upload wizard. Accepts CSV or Excel
 * (.xlsx/.xls — the first sheet is converted to CSV on arrival), parses a
 * preview for column mapping, then walks every row through a caller-supplied
 * persister, collecting per-row outcomes so nothing fails silently.
 */
class CsvImportService
{
    /**
     * Store an upload under storage/app/private/imports/data as CSV, converting
     * the first sheet of an Excel workbook when needed. Returns the relative path.
     */
    public function storeUpload(UploadedFile $file): string
    {
        return $this->storeFile($file->getRealPath(), $file->getClientOriginalExtension());
    }

    /** Same as storeUpload() for a file already on disk (console imports). The source is copied, never moved. */
    public function storeFile(string $source, ?string $ext = null): string
    {
        $ext = strtolower($ext ?: pathinfo($source, PATHINFO_EXTENSION));
        $dir = storage_path('app/private/imports/data');
        if (! is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        $name = uniqid('upload_', true).'.csv';
        $target = $dir.DIRECTORY_SEPARATOR.$name;

        if (in_array($ext, ['xlsx', 'xls'], true)) {
            if (! class_exists(IOFactory::class)) {
                throw new \RuntimeException('Excel uploads need the spreadsheet library — save the sheet as CSV (UTF-8) and upload that instead.');
            }
            $reader = IOFactory::createReaderForFile($source);
            $reader->setReadDataOnly(true);
            $sheet = $reader->load($source)->getSheet(0);
            $out = fopen($target, 'w');
            foreach ($sheet->toArray(null, true, true, false) as $row) {
                if (array_filter($row, fn ($c) => $c !== null && trim((string) $c) !== '') === []) {
                    continue; // fully blank row
                }
                fputcsv($out, array_map(fn ($c) => $c === null ? '' : (string) $c, $row), ',', '"', '', "\n");
            }
            fclose($out);
        } else {
            copy($source, $target);
        }

        return 'imports/data/'.$name;
    }

    /**
     * Parse a CSV into [header, sampleRows].
     *
     * @return array{0:array<int,string>,1:array<int,array<int,string>>}
     */
    public function parse(string $file, int $limit = 8): array
    {
        $handle = fopen($file, 'r');
        if ($handle === false) {
            return [[], []];
        }

        $header = fgetcsv($handle, null, ',', '"', '') ?: [];
        if (isset($header[0])) {
            $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $header[0]); // strip UTF-8 BOM
        }

        $rows = [];
        while (count($rows) < $limit && ($row = fgetcsv($handle, null, ',', '"', '')) !== false) {
            if ($row === [null] || $row === false) {
                continue;
            }
            $rows[] = $row;
        }
        fclose($handle);

        return [$header, $rows];
    }

    /**
     * Import every data row. $map is field => column index. $persist receives the
     * resolved field=>value array (+ line number) and returns 'created' |
     * 'updated' | null (skip), or throws with a human message to log a row error.
     *
     * @param  array<string,int|string|null>  $map
     * @return array{created:int,updated:int,skipped:int,errors:array<int,array{row:int,message:string}>}
     */
    public function import(string $file, array $map, callable $persist): array
    {
        $handle = fopen($file, 'r');
        if ($handle === false) {
            return ['created' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => [['row' => 0, 'message' => 'Could not open the uploaded file.']]];
        }

        fgetcsv($handle, null, ',', '"', ''); // header
        $result = ['created' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => []];
        $line = 1;

        while (($row = fgetcsv($handle, null, ',', '"', '')) !== false) {
            $line++;
            if ($row === [null] || array_filter($row, fn ($c) => trim((string) $c) !== '') === []) {
                continue; // blank line
            }

            $values = [];
            foreach ($map as $field => $index) {
                $values[$field] = ($index === null || $index === '') ? null : trim((string) ($row[(int) $index] ?? ''));
            }

            try {
                $outcome = $persist($values, $line);
                if ($outcome === 'created') {
                    $result['created']++;
                } elseif ($outcome === 'updated') {
                    $result['updated']++;
                } else {
                    $result['skipped']++;
                }
            } catch (\Throwable $e) {
                $result['skipped']++;
                if (count($result['errors']) < 200) {
                    $result['errors'][] = ['row' => $line, 'message' => $e->getMessage()];
                }
            }
        }
        fclose($handle);

        return $result;
    }

    /**
     * Guess which column holds a field: exact label/key match first (so our own
     * templates map perfectly), then keyword search in keyword order — earlier
     * keywords win, so "description" beats "desc" matching "Category Desc".
     */
    public function guess(array $header, array $keywords, ?string $label = null, ?string $key = null): ?int
    {
        $norm = fn ($s) => preg_replace('/[^a-z0-9]+/', '', strtolower((string) $s));
        foreach ([$label, $key] as $exact) {
            if ($exact === null) {
                continue;
            }
            foreach ($header as $i => $name) {
                if ($norm($name) === $norm($exact)) {
                    return $i;
                }
            }
        }
        foreach ($keywords as $kw) {
            foreach ($header as $i => $name) {
                if (str_contains(mb_strtolower((string) $name), $kw)) {
                    return $i;
                }
            }
        }

        return null;
    }
}
