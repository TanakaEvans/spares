<?php

namespace App\Http\Controllers;

use App\Services\DocumentIdentityService;
use App\Services\DocumentStampService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Public document-verification landing page (the QR-code target). Recomputes
 * the HMAC over the scanned token and shows whether the stamp is authentic,
 * alongside the exact figures that were signed. No login required — it only
 * reveals what is already printed on the document.
 */
class VerifyController extends Controller
{
    public function show(Request $request, DocumentStampService $stamp, DocumentIdentityService $identity): View
    {
        $token = (string) $request->query('d', '');
        $sig = (string) $request->query('c', '');

        $data = ($token !== '' && $sig !== '') ? $stamp->verify($token, $sig) : null;
        $company = $identity->for(null)['company_name'] ?? config('app.name');

        return view('verify.document', [
            'valid' => $data !== null,
            'data' => $data,
            'code' => $data !== null ? $stamp->codeForToken($token) : null,
            'company' => $company,
        ]);
    }
}
