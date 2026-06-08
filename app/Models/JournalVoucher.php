<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $voucher_number
 * @property \Illuminate\Support\Carbon $voucher_date
 * @property string|null $narration
 * @property string $status
 * @property int|null $created_by
 */
class JournalVoucher extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'voucher_number',
        'voucher_date',
        'narration',
        'status',
        'created_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'voucher_date' => 'date',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return HasMany<JournalVoucherLine, $this>
     */
    public function lines(): HasMany
    {
        return $this->hasMany(JournalVoucherLine::class);
    }
}
