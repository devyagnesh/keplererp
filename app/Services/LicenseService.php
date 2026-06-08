<?php

namespace App\Services;

use App\Models\License;
use Illuminate\Support\Facades\Cache;

/**
 * Validates on-premise license (SRS §18).
 */
class LicenseService
{
    /**
     * Whether the application may accept write operations.
     */
    public function isValid(): bool
    {
        return Cache::remember('license.valid', 300, function (): bool {
            $license = License::query()->where('is_active', true)->orderByDesc('id')->first();
            if ($license === null) {
                return true;
            }

            if ($license->expires_at->endOfDay()->isPast()) {
                return false;
            }

            if (app()->environment('local', 'testing')) {
                return true;
            }

            $expectedHost = parse_url((string) config('app.url'), PHP_URL_HOST);
            if ($expectedHost === null || $expectedHost === '') {
                return true;
            }

            $licenseHost = strtolower($license->domain);
            $expectedHost = strtolower($expectedHost);

            if ($licenseHost === $expectedHost) {
                return true;
            }

            if (in_array($licenseHost, ['localhost', '127.0.0.1'], true)) {
                return true;
            }

            if (str_ends_with($expectedHost, '.test') && str_ends_with($licenseHost, '.test')) {
                return true;
            }

            return false;
        });
    }

    /**
     * Days until expiry for dashboard / alerts.
     */
    public function daysUntilExpiry(): ?int
    {
        $license = License::query()->where('is_active', true)->orderByDesc('id')->first();
        if ($license === null) {
            return null;
        }

        return (int) now()->startOfDay()->diffInDays($license->expires_at, false);
    }

    public function forgetCache(): void
    {
        Cache::forget('license.valid');
    }
}
