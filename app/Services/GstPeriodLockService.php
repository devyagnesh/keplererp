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

    /**
     * Record GSTR-1 / GSTR-3B filing ARN after return submission (SRS UC 22.5).
     */
    public function recordFiling(
        int $year,
        int $month,
        ?string $gstr1Arn = null,
        ?string $gstr3bArn = null,
        ?string $gstr3bTaxPaid = null,
        ?User $user = null
    ): GstPeriodLock {
        $lock = GstPeriodLock::query()->firstOrCreate(
            ['period_year' => $year, 'period_month' => $month],
            [
                'locked_by' => $user?->id ?? User::query()->value('id'),
                'locked_at' => now(),
            ]
        );

        $updates = [];
        if ($gstr1Arn !== null && $gstr1Arn !== '') {
            $updates['gstr1_arn'] = $gstr1Arn;
            $updates['gstr1_filed_at'] = now();
        }
        if ($gstr3bArn !== null && $gstr3bArn !== '') {
            $updates['gstr3b_arn'] = $gstr3bArn;
            $updates['gstr3b_filed_at'] = now();
        }
        if ($gstr3bTaxPaid !== null) {
            $updates['gstr3b_tax_paid'] = $gstr3bTaxPaid;
        }

        if ($updates !== []) {
            $lock->update($updates);
        }

        return $lock->fresh();
    }
}
