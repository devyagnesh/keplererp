<?php

namespace App\Console\Commands;

use App\Services\LicenseService;
use Illuminate\Console\Command;

/**
 * Clears cached license validation (SRS §18: license:refresh).
 */
class RefreshLicenseCommand extends Command
{
    protected $signature = 'license:refresh';

    protected $description = 'Refresh cached license validation after AMC renewal.';

    public function handle(LicenseService $license): int
    {
        $license->forgetCache();
        $valid = $license->isValid();
        $days = $license->daysUntilExpiry();
        $this->components->info($valid
            ? 'License is valid.'.($days !== null ? " Expires in {$days} day(s)." : '')
            : 'License is invalid or expired.');

        return $valid ? self::SUCCESS : self::FAILURE;
    }
}
