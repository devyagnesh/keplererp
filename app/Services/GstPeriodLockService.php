<?php

namespace App\Services;

use App\Models\GstPeriodLock;
use App\Models\Invoice;
use App\Models\User;
use Carbon\Carbon;
use InvalidArgumentException;

/**
 * GST return period locking (SRS addendum UC 22.5).
 */
class GstPeriodLockService
{
    public function isLocked(Carbon $date): bool
    {
        return GstPeriodLock::query()
            ->where('period_year', $date->year)
            ->where('period_month', $date->month)
            ->exists();
    }

    /**
     * @throws InvalidArgumentException
     */
    public function lock(int $year, int $month, User $user): GstPeriodLock
    {
        $draftInvoices = Invoice::query()
            ->whereYear('invoice_date', $year)
            ->whereMonth('invoice_date', $month)
            ->where('status', 'draft')
            ->exists();

        if ($draftInvoices) {
            throw new InvalidArgumentException('Cannot lock period: draft invoices still exist for this month.');
        }

        return GstPeriodLock::query()->firstOrCreate(
            ['period_year' => $year, 'period_month' => $month],
            ['locked_by' => $user->id, 'locked_at' => now()]
        );
    }

    public function assertNotLocked(Carbon $date): void
    {
        if ($this->isLocked($date)) {
            throw new InvalidArgumentException(
                'GST period '.$date->format('Y-m').' is locked. No backdated invoices allowed.'
            );
        }
    }
}
