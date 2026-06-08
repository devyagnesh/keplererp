<?php

namespace App\Http\Controllers\Employee;

use App\Enums\PdfDocumentType;
use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\PayrollDetail;
use App\Services\Pdf\PdfGeneratorService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

/**
 * Employee self-service payslip list and download (linked User → Employee).
 */
class EmployeePayslipController extends Controller
{
    public function __construct(
        protected PdfGeneratorService $pdfGenerator
    ) {}

    public function index(Request $request): View
    {
        $employee = $this->resolveEmployee($request);

        $details = PayrollDetail::query()
            ->where('employee_id', $employee->id)
            ->whereHas('payrollRun', fn ($q) => $q->where('status', 'processed'))
            ->with(['payrollRun:id,period_year,period_month,processed_at'])
            ->orderByDesc('id')
            ->get();

        return view('employee.payslips-index', [
            'employee' => $employee,
            'details' => $details,
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

    protected function resolveEmployee(Request $request): Employee
    {
        $user = $request->user();
        if ($user === null) {
            abort(403);
        }

        $employee = Employee::query()
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->first();

        if ($employee === null) {
            abort(403, 'No employee profile is linked to your account.');
        }

        return $employee;
    }
}
