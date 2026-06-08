<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $period_year
 * @property int $period_month
 * @property string $status
 * @property int|null $processed_by
 * @property \Illuminate\Support\Carbon|null $processed_at
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
        'processed_by',
        'processed_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'processed_at' => 'datetime',
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
