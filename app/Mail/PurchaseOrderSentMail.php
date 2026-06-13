<?php

namespace App\Mail;

use App\Models\GeneratedDocument;
use App\Models\PurchaseOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PurchaseOrderSentMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public PurchaseOrder $purchaseOrder,
        public ?GeneratedDocument $document = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Purchase order '.$this->purchaseOrder->po_number,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.purchase-order-sent',
            with: [
                'poNumber' => $this->purchaseOrder->po_number,
                'vendorName' => $this->purchaseOrder->vendor?->name ?? 'Vendor',
                'total' => (string) $this->purchaseOrder->total_amount,
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
