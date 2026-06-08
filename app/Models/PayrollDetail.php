<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Per-employee payslip line for a payroll run.
 *
 * @property int $id
 * @property int $payroll_run_id
 * @property int $employee_id
 * @property string $basic_salary
 * @property string $hra
 * @property string $gross_salary
 * @property string $pf_deduction
 * @property string $esi_deduction
 * @property string $professional_tax
 * @property string $net_salary
 */
class PayrollDetail extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'payroll_run_id',
        'employee_id',
        'working_days',
        'present_days',
        'lop_days',
        'basic_salary',
        'hra',
        'conveyance',
        'gross_salary',
        'pf_deduction',
        'esi_deduction',
        'professional_tax',
        'tds',
        'other_deductions',
        'pf_employer',
        'esi_employer',
        'net_salary',
        'payment_status',
        'earnings_breakdown',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'earnings_breakdown' => 'array',
        ];
    }

    /**
     * @return BelongsTo<PayrollRun, $this>
     */
    public function payrollRun(): BelongsTo
    {
        return $this->belongsTo(PayrollRun::class);
    }

    /**
     * @return BelongsTo<Employee, $this>
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
