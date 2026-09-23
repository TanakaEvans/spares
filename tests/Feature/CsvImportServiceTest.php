<?php

namespace Tests\Feature;

use App\Services\CsvImportService;
use Tests\TestCase;

/**
 * The generic CSV import engine: header/BOM parsing and per-row outcomes with
 * error capture (used by the master-data import wizard).
 */
class CsvImportServiceTest extends TestCase
{
    private function tmpCsv(string $contents): string
    {
        $path = tempnam(sys_get_temp_dir(), 'imp').'.csv';
        file_put_contents($path, $contents);

        return $path;
    }

    public function test_parse_reads_header_and_strips_bom(): void
    {
        $file = $this->tmpCsv("\xEF\xBB\xBFname,phone\nAcme,111\nBeta,222\n");
        [$header, $rows] = app(CsvImportService::class)->parse($file);

        $this->assertSame(['name', 'phone'], $header);
        $this->assertCount(2, $rows);
        $this->assertSame('Acme', $rows[0][0]);
        @unlink($file);
    }

    public function test_import_counts_outcomes_and_captures_row_errors(): void
    {
        $file = $this->tmpCsv("name,phone\nAcme,111\n,999\nBeta,222\n");

        $result = app(CsvImportService::class)->import(
            $file,
            ['name' => 0, 'phone' => 1],
            fn (array $v) => ($v['name'] === '') ? throw new \RuntimeException('Name is empty.') : 'created',
        );

        $this->assertSame(2, $result['created']);
        $this->assertSame(1, $result['skipped']);
        $this->assertCount(1, $result['errors']);
        $this->assertSame('Name is empty.', $result['errors'][0]['message']);
        $this->assertSame(3, $result['errors'][0]['row']); // header is line 1
        @unlink($file);
    }
}
