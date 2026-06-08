<?php

namespace App\Mail;

use App\Models\PurchaseOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PurchaseOrderSentMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public PurchaseOrder $purchaseOrder) {}

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
}
