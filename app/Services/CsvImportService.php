<?php

namespace App\Services;

/**
 * Generic CSV import engine used by the master-data import wizard. Parses an
 * uploaded file for column mapping, then walks every row through a caller-
 * supplied persister, collecting per-row outcomes so nothing fails silently.
 */
class CsvImportService
{
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
        // Strip a UTF-8 BOM off the first header cell if present.
        if (isset($header[0])) {
            $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $header[0]);
        }

        $rows = [];
        while (count($rows) < $limit && ($row = fgetcsv($handle, null, ',', '"', '')) !== false) {
            if ($row === [null] || $row === false) {
                continue; // blank line
            }
            $rows[] = $row;
        }
        fclose($handle);

        return [$header, $rows];
    }

    /**
     * Import every data row. $map is field => column index. $persist receives the
     * resolved field=>value array and returns 'created' | 'updated' | null (skip),
     * or throws with a human message to record a row error.
     *
     * @param  array<string,int|null>  $map
     * @return array{created:int,updated:int,skipped:int,errors:array<int,array{row:int,message:string}>}
     */
    public function import(string $file, array $map, callable $persist): array
    {
        $handle = fopen($file, 'r');
        if ($handle === false) {
            return ['created' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => [['row' => 0, 'message' => 'Could not open the uploaded file.']]];
        }

        fgetcsv($handle, null, ',', '"', ''); // discard header
        $result = ['created' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => []];
        $line = 1;

        while (($row = fgetcsv($handle, null, ',', '"', '')) !== false) {
            $line++;
            if ($row === [null]) {
                continue; // blank line
            }

            $values = [];
            foreach ($map as $field => $index) {
                $values[$field] = ($index === null || $index === '') ? null : trim((string) ($row[$index] ?? ''));
            }

            try {
                $outcome = $persist($values);
                if ($outcome === 'created') {
                    $result['created']++;
                } elseif ($outcome === 'updated') {
                    $result['updated']++;
                } else {
                    $result['skipped']++;
                }
            } catch (\Throwable $e) {
                $result['skipped']++;
                if (count($result['errors']) < 100) {
                    $result['errors'][] = ['row' => $line, 'message' => $e->getMessage()];
                }
            }
        }
        fclose($handle);

        return $result;
    }

    /** Best-effort guess of the column index matching any keyword. */
    public function guess(array $header, array $keywords): ?int
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
