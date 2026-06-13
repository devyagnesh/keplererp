<?php

namespace App\Services;

use App\Enums\PdfDocumentType;
use App\Mail\PayslipProcessedMail;
use App\Models\AttendanceEntry;
use App\Models\Employee;
use App\Models\PayrollDetail;
use App\Models\PayrollRun;
use App\Models\PayrollSetting;
use App\Models\User;
use App\Services\Payroll\PayrollArrearService;
use App\Services\Pdf\PdfGeneratorService;
use App\Services\WhatsApp\WhatsAppNotificationService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use InvalidArgumentException;
use Throwable;

/**
 * Monthly payroll using HR-configured statutory rules and allowance types.
 */
class PayrollRunService
{
    public function __construct(
        protected AccountingJournalService $journal,
        protected WhatsAppNotificationService $whatsapp,
        protected AuditLogService $auditLog,
        protected PdfGeneratorService $pdfGenerator,
        protected PayrollCalculationService $payrollCalc,
        protected PayrollArrearService $arrears
    ) {}

    /**
     * @throws Throwable
     */
    public function process(PayrollRun $run, User $user): void
    {
        if ($run->status !== 'draft') {
            throw new InvalidArgumentException('Only draft payroll runs can be processed.');
        }

        if (! $run->attendance_locked) {
            throw new InvalidArgumentException('Attendance must be locked before processing payroll.');
        }

        DB::transaction(function () use ($run, $user): void {
            PayrollDetail::query()->where('payroll_run_id', $run->id)->delete();

            $settings = PayrollSetting::current();
            $employees = Employee::query()
                ->where('is_active', true)
                ->with(['employeeAllowances.allowanceType'])
                ->get();

            if ($employees->isEmpty()) {
                throw new InvalidArgumentException('Cannot process payroll: no active employees found.');
            }

            $periodStart = Carbon::create($run->period_year, $run->period_month, 1)->startOfMonth();
            $periodEnd = $periodStart->copy()->endOfMonth();
            $workingDays = (int) $periodStart->daysInMonth;

            $totalNet = '0.00';
            $totalPf = '0.00';
            $totalEsi = '0.00';
            $totalPt = '0.00';
            $totalTds = '0.00';
            $totalGross = '0.00';

            foreach ($employees as $employee) {
                $calc = $this->calculateForEmployee($employee, $periodStart, $periodEnd, $workingDays, $settings);
                $arrear = $this->arrears->settlePendingForEmployee($employee, $run);
                if (bccomp($arrear['amount'], '0', 2) > 0) {
                    $calc['arrear_amount'] = $arrear['amount'];
                    $calc['arrear_note'] = $arrear['note'];
                    $calc['gross_salary'] = bcadd((string) $calc['gross_salary'], $arrear['amount'], 2);
                    $calc['net_salary'] = bcadd((string) $calc['net_salary'], $arrear['amount'], 2);
                    $breakdown = is_array($calc['earnings_breakdown']) ? $calc['earnings_breakdown'] : [];
                    $breakdown['arrear'] = ['label' => $arrear['note'] ?? 'Arrear', 'amount' => $arrear['amount']];
                    $calc['earnings_breakdown'] = $breakdown;
                }
                PayrollDetail::query()->create([
                    'payroll_run_id' => $run->id,
                    'employee_id' => $employee->id,
                    ...$calc,
                ]);
                $totalNet = bcadd($totalNet, $calc['net_salary'], 2);
                $totalPf = bcadd($totalPf, $calc['pf_deduction'], 2);
                $totalEsi = bcadd($totalEsi, $calc['esi_deduction'], 2);
                $totalPt = bcadd($totalPt, $calc['professional_tax'], 2);
                $totalTds = bcadd($totalTds, $calc['tds'], 2);
                $totalGross = bcadd($totalGross, $calc['gross_salary'], 2);
            }

            if (bccomp($totalGross, '0', 2) > 0) {
                $journalLines = [
                    ['code' => 'SALARY-EXP', 'debit' => $totalGross, 'credit' => '0.00'],
                    ['code' => 'BANK-MAIN', 'debit' => '0.00', 'credit' => $totalNet],
                    ['code' => 'PF-PAYABLE', 'debit' => '0.00', 'credit' => $totalPf],
                    ['code' => 'ESI-PAYABLE', 'debit' => '0.00', 'credit' => $totalEsi],
                    ['code' => 'PT-PAYABLE', 'debit' => '0.00', 'credit' => $totalPt],
                ];
                if (bccomp($totalTds, '0', 2) > 0) {
                    $journalLines[] = ['code' => 'TDS-PAYABLE', 'debit' => '0.00', 'credit' => $totalTds];
                }
                $this->journal->post(
                    PayrollRun::class,
                    $run->id,
                    'Payroll '.$run->period_year.'-'.$run->period_month,
                    $user->id,
                    $journalLines
                );
            }

            $run->update([
                'status' => 'processed',
                'processed_by' => $user->id,
                'processed_at' => now(),
            ]);

            $this->auditLog->record(
                'payroll.processed',
                'Calculated payroll '.$run->period_year.'-'.$run->period_month,
                $run,
                $user
            );
        });
    }

    /**
     * HR approves calculated payroll and triggers payslip delivery (SRS UC 22.4).
     *
     * @throws Throwable
     */
    public function approve(PayrollRun $run, User $user): void
    {
        if ($run->status !== 'processed') {
            throw new InvalidArgumentException('Only processed payroll runs can be approved.');
        }

        DB::transaction(function () use ($run, $user): void {
            $details = PayrollDetail::query()
                ->where('payroll_run_id', $run->id)
                ->with('employee')
                ->get();

            $freshRun = $run->fresh();
            foreach ($details as $detail) {
                $document = $this->pdfGenerator->generate(PdfDocumentType::Payslip, $detail, $user->id);
                $payslipUrl = $this->pdfGenerator->signedDownloadUrl($document);
                if ($detail->employee !== null && $freshRun !== null) {
                    $this->whatsapp->notifySalaryCredited(
                        $detail->employee,
                        $freshRun,
                        (string) $detail->net_salary,
                        $payslipUrl
                    );
                    $email = $detail->employee->email;
                    if ($email !== null && $email !== '') {
                        Mail::to($email)->queue(
                            new PayslipProcessedMail($detail, $document)
                        );
                    }
                }
            }

            if ($freshRun !== null) {
                $this->pdfGenerator->queue(PdfDocumentType::PayrollSummary, $freshRun, $user->id);
            }

            $run->update([
                'status' => 'approved',
                'approved_by' => $user->id,
                'approved_at' => now(),
            ]);

            $this->auditLog->record(
                'payroll.approved',
                'Approved payroll '.$run->period_year.'-'.$run->period_month,
                $run,
                $user
            );
        });
    }

    /**
     * @return array<string, string|int|array<string, mixed>|null>
     */
    public function calculateForEmployee(
        Employee $employee,
        Carbon $periodStart,
        Carbon $periodEnd,
        int $workingDays,
        ?PayrollSetting $settings = null
    ): array {
        $settings ??= PayrollSetting::current();

        $presentDays = AttendanceEntry::query()
            ->where('employee_id', $employee->id)
            ->whereBetween('work_date', [$periodStart->toDateString(), $periodEnd->toDateString()])
            ->whereIn('status', ['present', 'half', 'half_day'])
            ->count();

        $lopDays = AttendanceEntry::query()
            ->where('employee_id', $employee->id)
            ->whereBetween('work_date', [$periodStart->toDateString(), $periodEnd->toDateString()])
            ->where('status', 'absent')
            ->count();

        $basic = bcadd((string) $employee->basic_salary, '0', 2);
        $perDayBasic = bcdiv($basic, (string) $workingDays, 4);
        $lopDeduction = bcmul($perDayBasic, (string) $lopDays, 2);
        $adjustedBasic = bcsub($basic, $lopDeduction, 2);
        if (bccomp($adjustedBasic, '0', 2) < 0) {
            $adjustedBasic = '0.00';
        }

        $allowances = $this->payrollCalc->sumAllowances($employee);
        $legacy = $this->payrollCalc->legacyAllowanceColumns($allowances['by_code']);
        $gross = bcadd($adjustedBasic, $allowances['total'], 2);

        $pf = $this->payrollCalc->calculatePf($employee, $adjustedBasic, $settings);
        $esiGross = $this->payrollCalc->grossForEsi($adjustedBasic, $employee);
        $esi = $this->payrollCalc->calculateEsi($employee, $esiGross, $settings);
        $professionalTax = $this->payrollCalc->calculateProfessionalTax($gross, $settings);
        $tds = bcadd((string) ($employee->monthly_tds ?? '0'), '0', 2);
        $otherDeductions = '0.00';
        $deductions = bcadd(
            bcadd(bcadd(bcadd($pf['pf_employee'], $esi['esi_employee'], 2), $professionalTax, 2), $tds, 2),
            $otherDeductions,
            2
        );
        $net = bcsub($gross, $deductions, 2);
        if (bccomp($net, '0', 2) < 0) {
            $net = '0.00';
        }

        return [
            'working_days' => $workingDays,
            'present_days' => $presentDays,
            'lop_days' => $lopDays,
            'basic_salary' => $adjustedBasic,
            'hra' => $legacy['hra'],
            'conveyance' => $legacy['conveyance'],
            'earnings_breakdown' => $this->payrollCalc->buildEarningsBreakdown($adjustedBasic, $allowances['lines']),
            'gross_salary' => $gross,
            'pf_deduction' => $pf['pf_employee'],
            'esi_deduction' => $esi['esi_employee'],
            'professional_tax' => $professionalTax,
            'tds' => $tds,
            'other_deductions' => $otherDeductions,
            'pf_employer' => $pf['pf_employer'],
            'esi_employer' => $esi['esi_employer'],
            'net_salary' => $net,
            'payment_status' => 'PENDING',
            'arrear_amount' => '0.00',
            'arrear_note' => null,
        ];
    }
}
