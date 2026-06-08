<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Posted journal line (debit/credit).
 *
 * @property int $id
 * @property int $journal_entry_id
 * @property int $account_id
 * @property string $debit
 * @property string $credit
 */
class AccountingJournalLine extends Model
{
    /**
     * @var string
     */
    protected $table = 'accounting_journal_lines';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'journal_entry_id',
        'account_id',
        'debit',
        'credit',
    ];

    /**
     * @return BelongsTo<AccountingJournalEntry, $this>
     */
    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(AccountingJournalEntry::class, 'journal_entry_id');
    }

    /**
     * @return BelongsTo<Account, $this>
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }
}
