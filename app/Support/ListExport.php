<?php

namespace App\Support;

use App\Services\DocumentPdfService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

/**
 * One entry point for list-screen exports: `?format=pdf` renders a branded
 * list PDF, anything else streams a CSV. Keeps every "Export" button consistent.
 */
class ListExport
{
    /**
     * @param  array<int,string>  $headers
     * @param  iterable<int,array<int,mixed>>  $rows
     */
    public static function respond(Request $request, string $filename, string $title, array $headers, iterable $rows): HttpResponse
    {
        if ($request->query('format') === 'pdf') {
            return app(DocumentPdfService::class)->render('print.list', [
                'title' => $title,
                'headers' => $headers,
                'rows' => collect($rows)->all(),
                'generatedAt' => now()->format('d M Y H:i'),
            ])->stream("{$filename}.pdf");
        }

        return CsvExport::download($filename, $headers, $rows);
    }
}
