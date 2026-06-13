<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Pending salary arrear for mid-month joiners (SRS UC 22.6).
 */
class PayrollArrear extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_SETTLED = 'settled';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'employee_id',
        'arrear_year',
        'arrear_month',
        'days_count',
        'amount',
        'note',
        'status',
        'settled_payroll_run_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'arrear_year' => 'integer',
            'arrear_month' => 'integer',
            'days_count' => 'integer',
            'amount' => 'decimal:2',
        ];
    }

    /**
     * @return BelongsTo<Employee, $this>
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * @return BelongsTo<PayrollRun, $this>
     */
    public function settledPayrollRun(): BelongsTo
    {
        return $this->belongsTo(PayrollRun::class, 'settled_payroll_run_id');
    }
}
