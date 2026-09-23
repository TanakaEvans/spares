<?php

namespace Tests\Feature;

use App\Mail\DocumentMail;
use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use App\Services\DocumentIdentityService;
use App\Services\DocumentPdfService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class DocumentPipelineTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::create(['name' => 'Superuser', 'description' => 'All access']);
        $this->admin = User::factory()->create(['password_changed_at' => now()]);
        $this->admin->roles()->attach($role->id);

        Company::factory()->create([
            'name' => 'SparesPro Motors',
            'vat_number' => 'VAT-220001234',
            'bank_name' => 'CBZ Bank',
            'bank_account_number' => '1002003004',
        ]);
    }

    public function test_proof_pdf_streams_with_identity(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.print.proof'));

        $response->assertOk();
        $this->assertStringContainsString('application/pdf', $response->headers->get('content-type'));
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_pdf_service_renders_identity_and_settings_into_view(): void
    {
        $pdf = app(DocumentPdfService::class)->render('print.proof', [
            'proofNumber' => 'PROOF-TEST',
            'date' => '23 Sep 2026',
            'printedBy' => 'Tester',
        ]);

        $html = $pdf->getDomPDF()->outputHtml();

        $this->assertStringContainsString('SparesPro Motors', $html);
        $this->assertStringContainsString('VAT-220001234', $html);
        $this->assertStringContainsString('CBZ Bank', $html);
        $this->assertStringContainsString('DOCUMENT PROOF', $html);
        $this->assertStringContainsString('90915-YZZD3', $html);
    }

    public function test_document_mail_queues_with_pdf_attachment(): void
    {
        Mail::fake();

        $identity = app(DocumentIdentityService::class)->for();

        Mail::to('customer@example.com')->send(new DocumentMail(
            subjectLine: 'Invoice INV-20260923-0001 from SparesPro Motors',
            heading: 'Your invoice is attached',
            bodyText: "Good day,\n\nPlease find invoice INV-20260923-0001 attached.",
            identity: $identity,
            pdfBinary: '%PDF-fake-binary',
            pdfFilename: 'INV-20260923-0001.pdf',
        ));

        Mail::assertQueued(DocumentMail::class, function (DocumentMail $mail) {
            return $mail->hasTo('customer@example.com')
                && $mail->pdfFilename === 'INV-20260923-0001.pdf'
                && count($mail->attachments()) === 1;
        });
    }

    public function test_document_mail_renders_identity_in_body(): void
    {
        $identity = app(DocumentIdentityService::class)->for();

        $mail = new DocumentMail(
            subjectLine: 'Test',
            heading: 'Statement for September',
            bodyText: 'Your statement is attached.',
            identity: $identity,
        );

        $rendered = $mail->render();

        $this->assertStringContainsString('SparesPro Motors', $rendered);
        $this->assertStringContainsString('VAT-220001234', $rendered);
        $this->assertStringContainsString('Statement for September', $rendered);
    }
}
