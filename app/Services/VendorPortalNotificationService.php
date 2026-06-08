<?php

namespace App\Services;

use App\Mail\VendorPortalCredentialsMail;
use App\Models\Vendor;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Sends vendor portal credential notifications (email only; not WhatsApp).
 */
class VendorPortalNotificationService
{
    /**
     * Email portal URL and temporary password to the vendor.
     *
     * @return bool True when the message was sent.
     */
    public function sendPortalCredentials(Vendor $vendor, string $plainPassword, string $portalUrl): bool
    {
        $email = trim((string) $vendor->email);
        if ($email === '') {
            Log::warning('Vendor portal credentials not emailed: vendor has no email address.', [
                'vendor_id' => $vendor->id,
                'vendor_code' => $vendor->vendor_code,
            ]);

            return false;
        }

        Mail::to($email)->send(new VendorPortalCredentialsMail(
            vendorName: $vendor->name,
            vendorCode: $vendor->vendor_code,
            portalUrl: $portalUrl,
            plainPassword: $plainPassword,
        ));

        return true;
    }
}
