<?php

namespace App\Services\Payroll;

use App\Models\PayrollDetail;
use App\Models\PayrollRun;
use Illuminate\Database\Eloquent\Collection;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Statutory payroll exports: PF ECR, ESI, Professional Tax (SRS UC 22.6).
 */
class PayrollStatutoryExportService
{
    /**
     * @throws InvalidArgumentException
     */
    public function downloadPfEcr(PayrollRun $run): StreamedResponse
    {
        $this->assertProcessed($run);
        $details = $this->loadDetails($run);
        $filename = sprintf('pf-ecr-%s-%02d.csv', $run->period_year, $run->period_month);

        return $this->streamCsv($filename, function ($handle) use ($details, $run): void {
            fputcsv($handle, ['UAN', 'Member Name', 'Gross Wages', 'EPF Wages', 'EPS Wages', 'EDLI Wages', 'EPF Contrib', 'EPS Contrib', 'EDLI Contrib', 'NCP Days', 'Refund', 'Period']);
            foreach ($details as $detail) {
                $emp = $detail->employee;
                if ($emp === null || ! $emp->pf_opted_in) {
                    continue;
                }
                $epfWages = min((float) $detail->basic_salary, 15000);
                fputcsv($handle, [
                    $emp->uan ?? '',
                    $emp->name,
                    $detail->gross_salary,
                    number_format($epfWages, 2, '.', ''),
                    number_format($epfWages, 2, '.', ''),
                    number_format($epfWages, 2, '.', ''),
                    $detail->pf_deduction,
                    '0.00',
                    '0.00',
                    $detail->lop_days ?? 0,
                    '0.00',
                    $run->period_year.'-'.str_pad((string) $run->period_month, 2, '0', STR_PAD_LEFT),
                ]);
            }
        });
    }

    /**
     * @throws InvalidArgumentException
     */
    public function downloadEsi(PayrollRun $run): StreamedResponse
    {
        $this->assertProcessed($run);
        $details = $this->loadDetails($run);
        $filename = sprintf('esi-%s-%02d.csv', $run->period_year, $run->period_month);

        return $this->streamCsv($filename, function ($handle) use ($details, $run): void {
            fputcsv($handle, ['IP Number', 'IP Name', 'No of Days', 'Total Monthly Wages', 'IP Contribution', 'Employer Contribution', 'Period']);
            foreach ($details as $detail) {
                $emp = $detail->employee;
                if ($emp === null || bccomp((string) $detail->esi_deduction, '0', 2) <= 0) {
                    continue;
                }
                fputcsv($handle, [
                    $emp->esi_number ?? '',
                    $emp->name,
                    $detail->present_days ?? 0,
                    $detail->gross_salary,
                    $detail->esi_deduction,
                    $detail->esi_employer ?? '0.00',
                    $run->period_year.'-'.str_pad((string) $run->period_month, 2, '0', STR_PAD_LEFT),
                ]);
            }
        });
    }

    /**
     * @throws InvalidArgumentException
     */
    public function downloadProfessionalTax(PayrollRun $run): StreamedResponse
    {
        $this->assertProcessed($run);
        $details = $this->loadDetails($run);
        $filename = sprintf('pt-challan-%s-%02d.csv', $run->period_year, $run->period_month);

        return $this->streamCsv($filename, function ($handle) use ($details, $run): void {
            fputcsv($handle, ['Employee Code', 'Employee Name', 'Gross Salary', 'PT Amount', 'Period']);
            $total = '0.00';
            foreach ($details as $detail) {
                if (bccomp((string) $detail->professional_tax, '0', 2) <= 0) {
                    continue;
                }
                fputcsv($handle, [
                    $detail->employee?->emp_code ?? '',
                    $detail->employee?->name ?? '',
                    $detail->gross_salary,
                    $detail->professional_tax,
                    $run->period_year.'-'.str_pad((string) $run->period_month, 2, '0', STR_PAD_LEFT),
                ]);
                $total = bcadd($total, (string) $detail->professional_tax, 2);
            }
            fputcsv($handle, ['', 'TOTAL', '', $total, '']);
        });
    }

    /**
     * @param  callable(resource): void  $writer
     */
    protected function streamCsv(string $filename, callable $writer): StreamedResponse
    {
        return response()->streamDownload(function () use ($writer): void {
            $handle = fopen('php://output', 'w');
            if ($handle === false) {
                return;
            }
            $writer($handle);
            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    /**
     * @return Collection<int, PayrollDetail>
     */
    protected function loadDetails(PayrollRun $run): Collection
    {
        return PayrollDetail::query()
            ->where('payroll_run_id', $run->id)
            ->with(['employee' => function ($q): void {
                $q->select(['id', 'emp_code', 'name', 'uan', 'esi_number', 'pf_opted_in']);
            }])
            ->orderBy('id')
            ->get();
    }

    protected function assertProcessed(PayrollRun $run): void
    {
        if (! in_array($run->status, ['approved', 'paid'], true)) {
            throw new InvalidArgumentException('Statutory export requires HR-approved payroll.');
        }
    }
}
