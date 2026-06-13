<?php

namespace App\Services\Payroll;

use App\Models\Employee;
use App\Models\PayrollArrear;
use App\Models\PayrollRun;
use Illuminate\Support\Facades\DB;

/**
 * Mid-month join arrear accrual and settlement (SRS UC 22.6).
 */
class PayrollArrearService
{
    /**
     * Queue arrear when employee joins after a payroll period was already processed.
     */
    public function queueForNewEmployee(Employee $employee): ?PayrollArrear
    {
        $joinDate = $employee->join_date;
        if ($joinDate === null) {
            return null;
        }

        $year = (int) $joinDate->year;
        $month = (int) $joinDate->month;
        $daysInMonth = (int) $joinDate->daysInMonth;

        if ($joinDate->day === 1) {
            return null;
        }

        $processed = PayrollRun::query()
            ->where('period_year', $year)
            ->where('period_month', $month)
            ->where('status', 'processed')
            ->exists();

        if (! $processed) {
            return null;
        }

        $daysWorked = $daysInMonth - $joinDate->day + 1;
        $basic = (string) $employee->basic_salary;
        $amount = bcmul(bcdiv($basic, (string) $daysInMonth, 4), (string) $daysWorked, 2);
        $monthLabel = $joinDate->format('F');

        return PayrollArrear::query()->updateOrCreate(
            [
                'employee_id' => $employee->id,
                'arrear_year' => $year,
                'arrear_month' => $month,
            ],
            [
                'days_count' => $daysWorked,
                'amount' => $amount,
                'note' => $monthLabel.' Arrear',
                'status' => PayrollArrear::STATUS_PENDING,
            ]
        );
    }

    /**
     * @return array{amount: string, note: string|null}
     */
    public function settlePendingForEmployee(Employee $employee, PayrollRun $run): array
    {
        $arrear = PayrollArrear::query()
            ->where('employee_id', $employee->id)
            ->where('status', PayrollArrear::STATUS_PENDING)
            ->lockForUpdate()
            ->first();

        if ($arrear === null) {
            return ['amount' => '0.00', 'note' => null];
        }

        DB::transaction(function () use ($arrear, $run): void {
            $arrear->update([
                'status' => PayrollArrear::STATUS_SETTLED,
                'settled_payroll_run_id' => $run->id,
            ]);
        });

        return [
            'amount' => (string) $arrear->amount,
            'note' => $arrear->note,
        ];
    }

    /**
     * Manually create arrear (admin override).
     */
    public function createManual(
        Employee $employee,
        int $year,
        int $month,
        int $days,
        string $amount,
        string $note = 'Salary Arrear'
    ): PayrollArrear {
        return PayrollArrear::query()->updateOrCreate(
            [
                'employee_id' => $employee->id,
                'arrear_year' => $year,
                'arrear_month' => $month,
            ],
            [
                'days_count' => $days,
                'amount' => $amount,
                'note' => $note,
                'status' => PayrollArrear::STATUS_PENDING,
            ]
        );
    }
}
