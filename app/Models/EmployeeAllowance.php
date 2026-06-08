<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Monthly allowance amount for an employee.
 *
 * @property int $employee_id
 * @property int $allowance_type_id
 * @property string $monthly_amount
 */
class EmployeeAllowance extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'employee_id',
        'allowance_type_id',
        'monthly_amount',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'monthly_amount' => 'decimal:2',
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
     * @return BelongsTo<AllowanceType, $this>
     */
    public function allowanceType(): BelongsTo
    {
        return $this->belongsTo(AllowanceType::class);
    }
}
