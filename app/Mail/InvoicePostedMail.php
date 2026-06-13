<?php

namespace App\Mail;

use App\Models\GeneratedDocument;
use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InvoicePostedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Invoice $invoice,
        public ?GeneratedDocument $document = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Invoice '.$this->invoice->invoice_number,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.invoice-posted',
            with: [
                'invoiceNumber' => $this->invoice->invoice_number,
                'total' => (string) $this->invoice->total_amount,
                'dueDate' => $this->invoice->due_date?->format('Y-m-d') ?? '—',
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
