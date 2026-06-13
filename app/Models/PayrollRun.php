<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $period_year
 * @property int $period_month
 * @property string $status
 * @property int|null $processed_by
 * @property Carbon|null $processed_at
 */
class PayrollRun extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'period_year',
        'period_month',
        'status',
        'attendance_locked',
        'processed_by',
        'processed_at',
        'approved_by',
        'approved_at',
        'paid_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'attendance_locked' => 'boolean',
            'processed_at' => 'datetime',
            'approved_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    /**
     * @return HasMany<PayrollDetail, $this>
     */
    public function details(): HasMany
    {
        return $this->hasMany(PayrollDetail::class);
    }
}
