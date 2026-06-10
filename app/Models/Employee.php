<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $emp_code
 * @property string $name
 * @property string|null $email
 * @property string|null $phone
 * @property string|null $department
 * @property string|null $designation
 * @property Carbon|null $join_date
 * @property int|null $user_id
 * @property bool $is_active
 * @property string $basic_salary
 * @property string|null $hra
 * @property bool $pf_opted_in
 */
class Employee extends Model
{
    use SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'emp_code',
        'name',
        'email',
        'phone',
        'department',
        'designation',
        'join_date',
        'user_id',
        'is_active',
        'basic_salary',
        'hra',
        'pf_number',
        'esi_number',
        'whatsapp',
        'department_id',
        'designation_id',
        'date_of_birth',
        'gender',
        'employment_type',
        'pan',
        'aadhaar',
        'bank_account_no',
        'bank_ifsc',
        'uan',
        'pf_opted_in',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'join_date' => 'date',
            'is_active' => 'boolean',
            'pf_opted_in' => 'boolean',
            'date_of_birth' => 'date',
            'basic_salary' => 'decimal:2',
            'hra' => 'decimal:2',
        ];
    }

    /**
     * @return HasMany<EmployeeAllowance, $this>
     */
    public function employeeAllowances(): HasMany
    {
        return $this->hasMany(EmployeeAllowance::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<AttendanceEntry, $this>
     */
    public function attendanceEntries(): HasMany
    {
        return $this->hasMany(AttendanceEntry::class);
    }

    /**
     * @return HasMany<LeaveApplication, $this>
     */
    public function leaveApplications(): HasMany
    {
        return $this->hasMany(LeaveApplication::class);
    }
}
