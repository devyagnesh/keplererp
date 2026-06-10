<?php

namespace App\Http\Controllers\Employee;

use App\Enums\PdfDocumentType;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Employee\Concerns\ResolvesLinkedEmployee;
use App\Models\PayrollDetail;
use App\Services\Pdf\PdfGeneratorService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

/**
 * Employee self-service payslip list and download (linked User → Employee).
 */
class EmployeePayslipController extends Controller
{
    use ResolvesLinkedEmployee;

    public function __construct(
        protected PdfGeneratorService $pdfGenerator
    ) {}

    public function index(Request $request): View
    {
        $employee = $this->resolveEmployee($request);

        $year = $request->query('year');
        $monthFrom = $request->query('month_from');
        $monthTo = $request->query('month_to');

        $query = PayrollDetail::query()
            ->where('employee_id', $employee->id)
            ->whereHas('payrollRun', function (Builder $q) use ($year, $monthFrom, $monthTo): void {
                $q->where('status', 'processed');
                if (is_numeric($year) && (int) $year > 2000) {
                    $q->where('period_year', (int) $year);
                }
                if (is_numeric($monthFrom) && (int) $monthFrom >= 1 && (int) $monthFrom <= 12) {
                    $q->where('period_month', '>=', (int) $monthFrom);
                }
                if (is_numeric($monthTo) && (int) $monthTo >= 1 && (int) $monthTo <= 12) {
                    $q->where('period_month', '<=', (int) $monthTo);
                }
            })
            ->with(['payrollRun:id,period_year,period_month,processed_at'])
            ->orderByDesc('id');

        $details = $query->get();

        $availableYears = PayrollDetail::query()
            ->where('employee_id', $employee->id)
            ->whereHas('payrollRun', fn (Builder $q) => $q->where('status', 'processed'))
            ->join('payroll_runs', 'payroll_details.payroll_run_id', '=', 'payroll_runs.id')
            ->distinct()
            ->orderByDesc('payroll_runs.period_year')
            ->pluck('payroll_runs.period_year');

        return view('employee.payslips.index', [
            'employee' => $employee,
            'details' => $details,
            'availableYears' => $availableYears,
            'selectedYear' => is_numeric($year) ? (int) $year : '',
            'selectedMonthFrom' => is_numeric($monthFrom) ? (int) $monthFrom : '',
            'selectedMonthTo' => is_numeric($monthTo) ? (int) $monthTo : '',
        ]);
    }

    public function downloadPdf(Request $request, PayrollDetail $payrollDetail): Response
    {
        $employee = $this->resolveEmployee($request);

        if ((int) $payrollDetail->employee_id !== (int) $employee->id) {
            abort(403);
        }

        $payrollDetail->loadMissing('payrollRun');
        if ($payrollDetail->payrollRun?->status !== 'processed') {
            abort(404);
        }

        return $this->pdfGenerator->downloadOrGenerate(
            PdfDocumentType::Payslip,
            $payrollDetail,
            $request->user()?->id
        );
    }
}
