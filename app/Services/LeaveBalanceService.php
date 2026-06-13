<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\LeaveApplication;
use App\Models\LeaveBalance;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Leave entitlement initialization and balance deduction (SRS UC 22.6).
 */
class LeaveBalanceService
{
    /**
     * Default annual entitlements per leave type (days).
     *
     * @var array<string, float>
     */
    protected array $defaults = [
        'CL' => 12,
        'SL' => 12,
        'EL' => 15,
    ];

    /**
     * Initialize leave balances for a new employee for the current fiscal year.
     */
    public function initializeForEmployee(Employee $employee, ?int $fiscalYear = null): void
    {
        $year = $fiscalYear ?? $this->currentFiscalYear();
        $entitled = $this->proRatedEntitlements($employee, $year);

        foreach ($entitled as $leaveType => $days) {
            LeaveBalance::query()->updateOrCreate(
                [
                    'employee_id' => $employee->id,
                    'leave_type' => $leaveType,
                    'fiscal_year' => $year,
                ],
                [
                    'entitled_days' => $days,
                    'used_days' => 0,
                    'balance_days' => $days,
                ]
            );
        }
    }

    /**
     * Deduct leave days when an application is approved.
     *
     * @throws InvalidArgumentException
     */
    public function deductOnApproval(LeaveApplication $application): void
    {
        $days = $this->countLeaveDays($application->start_date, $application->end_date);
        if ($days <= 0) {
            return;
        }

        $year = $this->fiscalYearForDate($application->start_date);
        $balance = LeaveBalance::query()
            ->where('employee_id', $application->employee_id)
            ->where('leave_type', strtoupper($application->leave_type))
            ->where('fiscal_year', $year)
            ->lockForUpdate()
            ->first();

        if ($balance === null) {
            $employee = Employee::query()->findOrFail($application->employee_id);
            $this->initializeForEmployee($employee, $year);
            $balance = LeaveBalance::query()
                ->where('employee_id', $application->employee_id)
                ->where('leave_type', strtoupper($application->leave_type))
                ->where('fiscal_year', $year)
                ->lockForUpdate()
                ->first();
        }

        if ($balance === null || (float) $balance->balance_days < $days) {
            throw new InvalidArgumentException(
                'Insufficient leave balance. Available: '.($balance->balance_days ?? 0).' days.'
            );
        }

        DB::transaction(function () use ($balance, $days): void {
            $used = bcadd((string) $balance->used_days, (string) $days, 2);
            $remaining = bcsub((string) $balance->balance_days, (string) $days, 2);
            $balance->update([
                'used_days' => $used,
                'balance_days' => $remaining,
            ]);
        });
    }

    /**
     * @return array<string, float>
     */
    public function proRatedEntitlements(Employee $employee, int $fiscalYear): array
    {
        $join = $employee->join_date;
        $fiscalStart = Carbon::create($fiscalYear, 4, 1);
        $fiscalEnd = Carbon::create($fiscalYear + 1, 3, 31);
        $monthsInYear = 12;

        if ($join !== null && $join->between($fiscalStart, $fiscalEnd)) {
            $monthsWorked = max(1, (int) $join->diffInMonths($fiscalEnd) + 1);
            $monthsInYear = min(12, $monthsWorked);
        }

        $result = [];
        foreach ($this->defaults as $type => $annual) {
            $result[$type] = round($annual * ($monthsInYear / 12), 2);
        }

        return $result;
    }

    public function countLeaveDays(Carbon $start, Carbon $end): float
    {
        return (float) ($start->diffInDays($end) + 1);
    }

    public function currentFiscalYear(): int
    {
        return $this->fiscalYearForDate(now());
    }

    public function fiscalYearForDate(Carbon $date): int
    {
        return $date->month >= 4 ? $date->year : $date->year - 1;
    }
}
