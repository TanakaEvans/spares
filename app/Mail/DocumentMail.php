<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * The one outgoing-document email. Every document type (invoice, quote,
 * statement, PO…) sends through this: shared layout, identity, optional
 * PDF attachment. Queued with retry (architecture.md queue rules).
 */
class DocumentMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        public readonly string $subjectLine,
        public readonly string $heading,
        public readonly string $bodyText,
        public readonly array $identity,
        public readonly ?string $pdfBinary = null,
        public readonly ?string $pdfFilename = null,
    ) {
        $this->onQueue('emails');
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->subjectLine);
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.document',
            with: [
                'heading' => $this->heading,
                'bodyText' => $this->bodyText,
                'identity' => $this->identity,
            ],
        );
    }

    public function attachments(): array
    {
        if ($this->pdfBinary === null) {
            return [];
        }

        return [
            Attachment::fromData(fn () => $this->pdfBinary, $this->pdfFilename ?? 'document.pdf')
                ->withMime('application/pdf'),
        ];
    }
}
