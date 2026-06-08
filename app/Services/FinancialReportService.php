<?php

namespace App\Services;

use App\Models\Account;
use App\Models\AccountingJournalLine;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * P&L and balance sheet from posted journal lines (SRS finance reports).
 */
class FinancialReportService
{
    /**
     * @return Collection<int, object{account_code: string, account_name: string, account_type: string, balance: string}>
     */
    public function profitAndLoss(Carbon $from, Carbon $to): Collection
    {
        return $this->balancesByType(['revenue', 'expense'], $from, $to);
    }

    /**
     * @return Collection<int, object{account_code: string, account_name: string, account_type: string, balance: string}>
     */
    public function balanceSheetAsOf(Carbon $asOf): Collection
    {
        return $this->balancesByType(['asset', 'liability', 'equity'], null, $asOf);
    }

    /**
     * @param  list<string>  $types
     */
    protected function balancesByType(array $types, ?Carbon $from, Carbon $to): Collection
    {
        $query = AccountingJournalLine::query()
            ->join('accounting_journal_entries', 'accounting_journal_entries.id', '=', 'accounting_journal_lines.journal_entry_id')
            ->join('accounts', 'accounts.id', '=', 'accounting_journal_lines.account_id')
            ->whereIn('accounts.account_type', $types)
            ->where('accounting_journal_entries.posted_at', '<=', $to->endOfDay());

        if ($from !== null) {
            $query->where('accounting_journal_entries.posted_at', '>=', $from->startOfDay());
        }

        $rows = $query
            ->selectRaw('accounts.account_code, accounts.account_name, accounts.account_type')
            ->selectRaw('SUM(accounting_journal_lines.debit) - SUM(accounting_journal_lines.credit) as balance')
            ->groupBy('accounts.id', 'accounts.account_code', 'accounts.account_name', 'accounts.account_type')
            ->orderBy('accounts.account_code')
            ->get();

        return $rows->map(function ($row) {
            $balance = bcadd((string) ($row->balance ?? '0'), '0', 2);
            if (in_array($row->account_type, ['revenue', 'liability', 'equity'], true)) {
                $balance = bcmul($balance, '-1', 2);
            }

            return (object) [
                'account_code' => $row->account_code,
                'account_name' => $row->account_name,
                'account_type' => $row->account_type,
                'balance' => $balance,
            ];
        });
    }

    public function downloadProfitAndLossCsv(Carbon $from, Carbon $to): StreamedResponse
    {
        $rows = $this->profitAndLoss($from, $to);
        $filename = sprintf('profit-loss-%s-to-%s.csv', $from->format('Y-m-d'), $to->format('Y-m-d'));

        return $this->streamCsv($filename, $rows);
    }

    public function downloadBalanceSheetCsv(Carbon $asOf): StreamedResponse
    {
        $rows = $this->balanceSheetAsOf($asOf);
        $filename = 'balance-sheet-'.$asOf->format('Y-m-d').'.csv';

        return $this->streamCsv($filename, $rows);
    }

    /**
     * @param  Collection<int, object>  $rows
     */
    protected function streamCsv(string $filename, Collection $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($rows): void {
            $handle = fopen('php://output', 'w');
            if ($handle === false) {
                return;
            }
            fputcsv($handle, ['Account Code', 'Account Name', 'Type', 'Balance']);
            foreach ($rows as $row) {
                fputcsv($handle, [
                    $row->account_code,
                    $row->account_name,
                    $row->account_type,
                    $row->balance,
                ]);
            }
            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    /**
     * @return Collection<int, Account>
     */
    public function chartOfAccounts(): Collection
    {
        return Account::query()->orderBy('account_code')->get();
    }
}
