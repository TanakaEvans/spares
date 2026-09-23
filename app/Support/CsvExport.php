<?php

namespace App\Support;

use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Streamed CSV download helper. Keeps bulk exports memory-light and gives every
 * list screen the same, consistent export format.
 */
class CsvExport
{
    /**
     * @param  array<int,string>  $headers  Column headings.
     * @param  iterable<int,array<int,mixed>>  $rows  Row values in header order.
     */
    public static function download(string $filename, array $headers, iterable $rows): StreamedResponse
    {
        $filename = str_ends_with(strtolower($filename), '.csv') ? $filename : "{$filename}.csv";

        return response()->streamDownload(function () use ($headers, $rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM so Excel reads accents correctly
            fputcsv($out, $headers, ',', '"', '', "\n");
            foreach ($rows as $row) {
                fputcsv($out, $row, ',', '"', '', "\n");
            }
            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'no-store',
        ]);
    }
}
