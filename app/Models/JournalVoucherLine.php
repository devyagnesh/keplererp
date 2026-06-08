<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $journal_voucher_id
 * @property string $account_code
 * @property string|null $account_name
 * @property string $debit
 * @property string $credit
 */
class JournalVoucherLine extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'journal_voucher_id',
        'account_code',
        'account_name',
        'debit',
        'credit',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'debit' => 'decimal:2',
            'credit' => 'decimal:2',
        ];
    }

    /**
     * @return BelongsTo<JournalVoucher, $this>
     */
    public function journalVoucher(): BelongsTo
    {
        return $this->belongsTo(JournalVoucher::class);
    }
}
