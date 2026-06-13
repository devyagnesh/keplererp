<?php

namespace App\Services\Payroll;

use App\Models\PayrollDetail;
use App\Models\PayrollRun;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * NEFT/RTGS bank payment file export for processed payroll (SRS UC 22.6).
 */
class PayrollBankExportService
{
    /**
     * @throws InvalidArgumentException
     */
    public function downloadCsv(PayrollRun $run, string $format = 'icici'): StreamedResponse
    {
        if (! in_array($run->status, ['approved', 'paid'], true)) {
            throw new InvalidArgumentException('Bank file can only be exported after HR approval.');
        }

        $format = strtolower($format);
        if (! in_array($format, ['icici', 'hdfc'], true)) {
            throw new InvalidArgumentException('Unsupported bank format. Use icici or hdfc.');
        }

        $details = PayrollDetail::query()
            ->where('payroll_run_id', $run->id)
            ->with('employee:id,name,bank_account_no,bank_ifsc,emp_code')
            ->where('payment_status', '!=', 'PAID')
            ->orderBy('id')
            ->get();

        $filename = sprintf('payroll-bank-%s-%02d-%s.csv', $run->period_year, $run->period_month, $format);

        return response()->streamDownload(function () use ($details, $format, $run): void {
            $handle = fopen('php://output', 'w');
            if ($handle === false) {
                return;
            }

            if ($format === 'icici') {
                fputcsv($handle, ['Beneficiary Name', 'Account Number', 'IFSC', 'Amount', 'Narration', 'Employee Code']);
                foreach ($details as $detail) {
                    $emp = $detail->employee;
                    if ($emp === null || ! $emp->bank_account_no || ! $emp->bank_ifsc) {
                        continue;
                    }
                    fputcsv($handle, [
                        $emp->name,
                        $emp->bank_account_no,
                        $emp->bank_ifsc,
                        $detail->net_salary,
                        'Salary '.$run->period_year.'-'.str_pad((string) $run->period_month, 2, '0', STR_PAD_LEFT),
                        $emp->emp_code,
                    ]);
                }
            } else {
                fputcsv($handle, ['Debit Account', 'Beneficiary Name', 'Beneficiary Account', 'IFSC', 'Amount', 'Payment Date', 'Remarks']);
                $debitAccount = (string) config('payroll.bank_debit_account', '');
                foreach ($details as $detail) {
                    $emp = $detail->employee;
                    if ($emp === null || ! $emp->bank_account_no || ! $emp->bank_ifsc) {
                        continue;
                    }
                    fputcsv($handle, [
                        $debitAccount,
                        $emp->name,
                        $emp->bank_account_no,
                        $emp->bank_ifsc,
                        $detail->net_salary,
                        now()->format('d/m/Y'),
                        'SALARY',
                    ]);
                }
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
