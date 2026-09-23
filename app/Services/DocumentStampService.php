<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\PurchaseOrder;
use App\Models\SalesDocument;
use chillerlan\QRCode\Common\EccLevel;
use chillerlan\QRCode\Output\QRGdImagePNG;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

/**
 * Digital document stamp (anti-forgery). Binds a document's key facts into an
 * HMAC-SHA256 signature keyed by the app secret, then renders a QR code that
 * points at a public verification page carrying the signed token. Because the
 * secret never leaves the server, nobody can mint a valid stamp for altered
 * figures, and the QR always resolves to the genuine, signed values.
 */
class DocumentStampService
{
    /** Build the stamp payload (code + QR data URI) for a set of facts. */
    public function build(string $type, string $number, array $facts): array
    {
        $token = $this->encode(['t' => $type, 'n' => $number, 'f' => $facts, 'i' => now()->timestamp]);
        $sig = $this->sign($token);
        $verifyUrl = route('verify.document', ['d' => $token, 'c' => substr($sig, 0, 32)]);

        return [
            'type' => $type,
            'number' => $number,
            'facts' => $facts,
            'code' => $this->humanCode($sig),
            'issued_at' => now()->format('d M Y H:i'),
            'verify_url' => $verifyUrl,
            'qr' => $this->qr($verifyUrl),
        ];
    }

    /** Verify a scanned token against its signature. Returns the signed data or null. */
    public function verify(string $token, string $providedSig): ?array
    {
        $expected = substr($this->sign($token), 0, 32);
        if (! hash_equals($expected, $providedSig)) {
            return null;
        }

        return $this->decode($token);
    }

    /** Re-derive the human code from a token (for display on the verify page). */
    public function codeForToken(string $token): string
    {
        return $this->humanCode($this->sign($token));
    }

    // ── Per-document convenience builders ────────────────────────────────

    public function forSalesDocument(SalesDocument $doc): array
    {
        return $this->build($doc->document_type, $doc->document_number, [
            'Date' => $doc->document_date->format('d M Y'),
            'Total' => number_format((float) $doc->total_incl, 2),
            'Party' => $doc->customer?->name ?? 'Cash Customer',
        ]);
    }

    public function forPurchaseOrder(PurchaseOrder $po): array
    {
        return $this->build('purchase_order', $po->po_number, [
            'Date' => optional($po->order_date)->format('d M Y') ?? '—',
            'Total' => number_format((float) $po->total, 2),
            'Party' => $po->supplier?->name ?? '—',
        ]);
    }

    public function forStatement(Customer $customer, float $closing): array
    {
        return $this->build('statement', $customer->customer_number, [
            'As at' => now()->format('d M Y'),
            'Closing' => number_format($closing, 2),
            'Party' => $customer->name,
        ]);
    }

    // ── Internals ────────────────────────────────────────────────────────

    private function sign(string $token): string
    {
        return hash_hmac('sha256', $token, (string) config('app.key'));
    }

    /** First 12 hex of the signature, grouped for easy human cross-checking. */
    private function humanCode(string $sig): string
    {
        return implode('-', str_split(strtoupper(substr($sig, 0, 12)), 4));
    }

    private function encode(array $data): string
    {
        return rtrim(strtr(base64_encode((string) json_encode($data)), '+/', '-_'), '=');
    }

    private function decode(string $token): ?array
    {
        $json = base64_decode(strtr($token, '-_', '+/'), true);
        $data = $json === false ? null : json_decode($json, true);

        return is_array($data) ? $data : null;
    }

    /** Render a QR of $text as a base64 PNG data URI (GD — DomPDF friendly). */
    private function qr(string $text): string
    {
        $options = new QROptions([
            'outputInterface' => QRGdImagePNG::class,
            'eccLevel' => EccLevel::L,
            'scale' => 4,
            'imageBase64' => true,
            'quietzoneSize' => 2,
        ]);

        return (new QRCode($options))->render($text);
    }
}
