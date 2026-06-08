<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Posted double-entry journal header.
 *
 * @property int $id
 * @property string $reference_type
 * @property int $reference_id
 * @property string|null $narration
 * @property int|null $created_by
 * @property \Illuminate\Support\Carbon $posted_at
 */
class AccountingJournalEntry extends Model
{
    /**
     * @var string
     */
    protected $table = 'accounting_journal_entries';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'reference_type',
        'reference_id',
        'narration',
        'created_by',
        'posted_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'posted_at' => 'datetime',
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
     * @return HasMany<AccountingJournalLine, $this>
     */
    public function lines(): HasMany
    {
        return $this->hasMany(AccountingJournalLine::class, 'journal_entry_id');
    }
}
