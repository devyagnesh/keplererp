<?php

namespace App\Services;

use App\Models\SalesQuotation;
use Illuminate\Support\Carbon;

/**
 * Marks open quotations as expired when valid_until has passed (SRS §11.1).
 */
class SalesQuotationExpiryService
{
    /**
     * @return int Number of quotations updated to expired.
     */
    public function expireDueQuotations(?Carbon $asOf = null): int
    {
        $date = ($asOf ?? Carbon::today())->toDateString();

        return SalesQuotation::query()
            ->whereIn('status', ['draft', 'sent', 'accepted'])
            ->whereNotNull('valid_until')
            ->whereDate('valid_until', '<', $date)
            ->update(['status' => 'expired']);
    }
}
