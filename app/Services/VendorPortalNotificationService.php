<?php

namespace App\Services;

use App\Mail\VendorPortalCredentialsMail;
use App\Models\Vendor;
use App\Services\WhatsApp\WhatsAppNotificationService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Sends vendor portal credentials via email and WhatsApp (SRS WA-13).
 */
class VendorPortalNotificationService
{
    public function __construct(
        protected WhatsAppNotificationService $whatsapp
    ) {}

    /**
     * @return bool True when at least one channel delivered.
     */
    public function sendPortalCredentials(Vendor $vendor, string $plainPassword, string $portalUrl): bool
    {
        $sent = false;
        $email = trim((string) $vendor->email);
        if ($email !== '') {
            Mail::to($email)->send(new VendorPortalCredentialsMail(
                vendorName: $vendor->name,
                vendorCode: $vendor->vendor_code,
                portalUrl: $portalUrl,
                plainPassword: $plainPassword,
            ));
            $sent = true;
        } else {
            Log::warning('Vendor portal credentials not emailed: vendor has no email address.', [
                'vendor_id' => $vendor->id,
            ]);
        }

        $this->whatsapp->notifyVendorPortalCredentials($vendor, $portalUrl, $plainPassword);

        return $sent || $vendor->phone !== '';
    }
}
