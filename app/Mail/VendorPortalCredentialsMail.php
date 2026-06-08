<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Vendor portal login URL and temporary password (SRS WA-13).
 */
class VendorPortalCredentialsMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $vendorName,
        public string $vendorCode,
        public string $portalUrl,
        public string $plainPassword,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: config('app.name').' — Vendor portal login credentials',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.vendor-portal-credentials',
            with: [
                'vendorName' => $this->vendorName,
                'vendorCode' => $this->vendorCode,
                'portalUrl' => $this->portalUrl,
                'plainPassword' => $this->plainPassword,
            ],
        );
    }

    /**
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
