<?php

namespace App\Mail;

use App\Models\GeneratedDocument;
use App\Models\SalesQuotation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SalesQuotationSentMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public SalesQuotation $quotation,
        public ?GeneratedDocument $document = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Quotation '.$this->quotation->quote_number,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.sales-quotation-sent',
            with: [
                'quoteNumber' => $this->quotation->quote_number,
                'customerName' => $this->quotation->customer?->name ?? 'Customer',
                'validUntil' => $this->quotation->valid_until?->format('Y-m-d') ?? '—',
            ],
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        if ($this->document === null || $this->document->file_path === '') {
            return [];
        }

        return [
            Attachment::fromStorageDisk((string) config('pdf.disk', 'local'), $this->document->file_path)
                ->as($this->document->download_name)
                ->withMime('application/pdf'),
        ];
    }
}
