<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\DocumentPdfService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PrintProofController extends Controller
{
    public function __invoke(Request $request, DocumentPdfService $pdf): Response
    {
        $branchId = $request->integer('branch') ?: null;

        return $pdf->render('print.proof', [
            'proofNumber' => 'PROOF-'.now()->format('YmdHis'),
            'date' => now()->format('d M Y H:i'),
            'printedBy' => $request->user()->name,
        ], $branchId)->stream('document-proof.pdf');
    }
}
