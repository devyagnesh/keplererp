<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Employee leave entitlement and usage per fiscal year (SRS UC 22.6).
 */
class LeaveBalance extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'employee_id',
        'leave_type',
        'fiscal_year',
        'entitled_days',
        'used_days',
        'balance_days',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'fiscal_year' => 'integer',
            'entitled_days' => 'decimal:2',
            'used_days' => 'decimal:2',
            'balance_days' => 'decimal:2',
        ];
    }

    /**
     * @return BelongsTo<Employee, $this>
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
