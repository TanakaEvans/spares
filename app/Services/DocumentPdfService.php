<?php

namespace App\Services;

use App\Models\Branch;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as PdfDocument;

class DocumentPdfService
{
    public function __construct(
        private readonly DocumentIdentityService $identity,
        private readonly SettingsService $settings,
    ) {
    }

    /**
     * Render any print view with the shared identity + settings context merged in.
     * Every printed document goes through here — one pipeline, one identity block.
     */
    public function render(string $view, array $data = [], Branch|int|null $branch = null): PdfDocument
    {
        $branchId = $branch instanceof Branch ? $branch->id : $branch;

        return Pdf::loadView($view, array_merge([
            'identity' => $this->identity->for($branch),
            'footerText' => $this->settings->get('documents.invoice_footer_text', $branchId),
            'vatRate' => $this->settings->get('tax.vat_rate_default'),
        ], $data))->setPaper('a4');
    }
}
