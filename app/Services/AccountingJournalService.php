<?php

namespace App\Services;

use App\Models\AccountingJournalEntry;
use App\Models\AccountingJournalLine;
use App\Models\Account;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Throwable;

/**
 * Double-entry journal posting against seeded system accounts.
 */
class AccountingJournalService
{
    /**
     * @param  list<array{code: string, debit: string, credit: string}>  $lines
     *
     * @throws Throwable
     */
    public function post(string $referenceType, int $referenceId, ?string $narration, ?int $userId, array $lines): AccountingJournalEntry
    {
        return DB::transaction(function () use ($referenceType, $referenceId, $narration, $userId, $lines): AccountingJournalEntry {
            $totalDebit = '0.00';
            $totalCredit = '0.00';
            foreach ($lines as $line) {
                $totalDebit = bcadd($totalDebit, $line['debit'], 2);
                $totalCredit = bcadd($totalCredit, $line['credit'], 2);
            }
            if (bccomp($totalDebit, $totalCredit, 2) !== 0) {
                throw new InvalidArgumentException('Journal is not balanced.');
            }

            $entry = AccountingJournalEntry::query()->create([
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'narration' => $narration,
                'created_by' => $userId,
                'posted_at' => now(),
            ]);

            foreach ($lines as $line) {
                if (bccomp($line['debit'], '0', 2) === 0 && bccomp($line['credit'], '0', 2) === 0) {
                    continue;
                }
                $account = Account::query()->where('account_code', $line['code'])->firstOrFail();
                AccountingJournalLine::query()->create([
                    'journal_entry_id' => $entry->id,
                    'account_id' => $account->id,
                    'debit' => $line['debit'],
                    'credit' => $line['credit'],
                ]);
            }

            return $entry;
        });
    }

    public function accountIdByCode(string $code): int
    {
        return (int) Account::query()->where('account_code', $code)->value('id');
    }
}
