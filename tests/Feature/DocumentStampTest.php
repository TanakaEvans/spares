<?php

namespace Tests\Feature;

use App\Services\DocumentStampService;
use Tests\TestCase;

/**
 * Anti-forgery document stamp: the HMAC binds the printed facts, so a genuine
 * token verifies and any tampering (to the signature or the payload) fails.
 */
class DocumentStampTest extends TestCase
{
    private function tokenAndSig(array $stamp): array
    {
        parse_str((string) parse_url($stamp['verify_url'], PHP_URL_QUERY), $q);

        return [$q['d'], $q['c']];
    }

    public function test_genuine_stamp_verifies_and_returns_signed_facts(): void
    {
        $svc = app(DocumentStampService::class);
        $stamp = $svc->build('invoice', 'INV-1', ['Total' => '100.00', 'Party' => 'Acme']);
        [$token, $sig] = $this->tokenAndSig($stamp);

        $data = $svc->verify($token, $sig);

        $this->assertNotNull($data);
        $this->assertSame('INV-1', $data['n']);
        $this->assertSame('100.00', $data['f']['Total']);
        $this->assertMatchesRegularExpression('/^[0-9A-F]{4}-[0-9A-F]{4}-[0-9A-F]{4}$/', $stamp['code']);
    }

    public function test_tampered_signature_is_rejected(): void
    {
        $svc = app(DocumentStampService::class);
        [$token, $sig] = $this->tokenAndSig($svc->build('invoice', 'INV-1', ['Total' => '100.00']));

        $this->assertNull($svc->verify($token, substr($sig, 0, -2).'00'));
    }

    public function test_tampered_payload_no_longer_matches_its_signature(): void
    {
        $svc = app(DocumentStampService::class);
        [, $sig] = $this->tokenAndSig($svc->build('invoice', 'INV-1', ['Total' => '100.00']));

        // Forge a token that claims a bigger total; the original signature won't match it.
        $forged = rtrim(strtr(base64_encode((string) json_encode(
            ['t' => 'invoice', 'n' => 'INV-1', 'f' => ['Total' => '999.00'], 'i' => now()->timestamp]
        )), '+/', '-_'), '=');

        $this->assertNull($svc->verify($forged, $sig));
    }
}
