<?php

namespace App\Console\Commands;

use App\Models\License;
use App\Services\LicenseService;
use App\Services\WhatsApp\WhatsAppNotificationService;
use Illuminate\Console\Command;

/**
 * Daily license AMC expiry WhatsApp to admins (SRS §17.2 WA-14).
 */
class SendLicenseExpiryWhatsAppAlerts extends Command
{
    protected $signature = 'whatsapp:send-license-expiry-alerts';

    protected $description = 'Queue WhatsApp reminders when license AMC expires within the warning window.';

    public function handle(WhatsAppNotificationService $whatsapp, LicenseService $licenseService): int
    {
        if (! $whatsapp->isEnabledForCompany()) {
            $this->components->info('WhatsApp is off or not configured.');

            return self::SUCCESS;
        }

        $license = License::query()->where('is_active', true)->orderByDesc('id')->first();
        if ($license === null) {
            $this->components->info('No active license record.');

            return self::SUCCESS;
        }

        $days = $licenseService->daysUntilExpiry();
        if ($days === null) {
            return self::SUCCESS;
        }

        $warnWithin = (int) config('license.warn_days_before_expiry', 30);
        if ($days > $warnWithin) {
            $this->components->info("License expires in {$days} day(s); outside {$warnWithin}-day warning window.");

            return self::SUCCESS;
        }

        $renewalUrl = (string) (config('license.renewal_url') ?: route('admin.license.expired'));
        $whatsapp->notifyLicenseExpiryReminder($days, $renewalUrl);

        $this->components->info("Queued license expiry alert ({$days} day(s) remaining).");

        return self::SUCCESS;
    }
}
