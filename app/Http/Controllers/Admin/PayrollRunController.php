<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PdfDocumentType;
use App\Http\Controllers\Controller;
use App\Http\Requests\StorePayrollRunRequest;
use App\Models\PayrollDetail;
use App\Models\PayrollRun;
use App\Models\User;
use App\Services\Payroll\PayrollBankExportService;
use App\Services\Payroll\PayrollStatutoryExportService;
use App\Services\PayrollRunService;
use App\Services\Pdf\PdfGeneratorService;
use App\Support\ErpDataTable;
use App\Support\PdfDownloadLink;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class PayrollRunController extends Controller
{
    public function __construct(
        protected PayrollRunService $payrollService,
        protected PdfGeneratorService $pdfGenerator,
        protected PayrollBankExportService $bankExport,
        protected PayrollStatutoryExportService $statutoryExport
    ) {}

    public function index(): View
    {
        $this->authorize('viewAny', PayrollRun::class);

        return view('admin.hr.payroll-index');
    }

    public function data(Request $request): JsonResponse
    {
        $this->authorize('viewAny', PayrollRun::class);

        $query = PayrollRun::query()->select(['id', 'period_year', 'period_month', 'status', 'processed_at', 'created_at']);

        $payload = ErpDataTable::run(
            $query,
            $request,
            function ($q, string $term): void {
                $q->where('status', 'like', '%'.$term.'%');
            },
            ['id', 'period_year', 'period_month', 'status', 'created_at'],
        );

        $actor = $request->user();
        if ($actor === null) {
            abort(403);
        }

        $data = $payload['rows']->map(function (PayrollRun $row) use ($actor) {
            return [
                'period' => $row->period_year.'-'.str_pad((string) $row->period_month, 2, '0', STR_PAD_LEFT),
                'status' => $row->status,
                'processed_at' => $row->processed_at?->format('Y-m-d H:i') ?? '—',
                'created_at' => $row->created_at?->format('Y-m-d H:i'),
                'action' => $this->buildActionHtml($row, $actor),
            ];
        })->values()->all();

        return response()->json([
            'draw' => $payload['draw'],
            'recordsTotal' => $payload['recordsTotal'],
            'recordsFiltered' => $payload['recordsFiltered'],
            'data' => $data,
        ]);
    }

    protected function buildActionHtml(PayrollRun $run, User $actor): string
    {
        $html = '<div class="btn-list d-flex flex-wrap gap-1">';
        if ($actor->can('update', $run) && $run->status === 'draft') {
            $html .= '<button type="button" class="btn btn-sm btn-success btn-wave js-payroll-process" data-url="'
                .e(route('admin.hr.payroll-runs.process', $run)).'">Process</button>';
        }
        if ($actor->can('approve', $run) && $run->status === 'processed') {
            $html .= '<button type="button" class="btn btn-sm btn-primary btn-wave js-payroll-approve" data-url="'
                .e(route('admin.hr.payroll-runs.approve', $run)).'">Approve</button>';
        }
        if ($actor->can('view', $run) && in_array($run->status, ['processed', 'approved', 'paid'], true)) {
            $html .= '<a href="'.e(route('admin.hr.payroll-runs.show', $run)).'" class="btn btn-sm btn-outline-primary btn-wave">Payslips</a>';
            $html .= PdfDownloadLink::button(route('admin.hr.payroll-runs.pdf', $run), 'Summary');
        }
        if ($actor->can('lockAttendance', $run) && $run->status === 'draft' && ! $run->attendance_locked) {
            $html .= '<button type="button" class="btn btn-sm btn-outline-warning btn-wave js-payroll-lock-attendance" data-url="'
                .e(route('admin.hr.payroll-runs.lock-attendance', $run)).'">Lock attendance</button>';
        }
        $html .= '</div>';

        return $html;
    }

    /**
     * List payslips for a processed payroll run.
     */
    public function show(PayrollRun $payrollRun): View
    {
        $this->authorize('view', $payrollRun);
        if ($payrollRun->status !== 'processed' && $payrollRun->status !== 'approved' && $payrollRun->status !== 'paid') {
            abort(404);
        }

        $details = PayrollDetail::query()
            ->where('payroll_run_id', $payrollRun->id)
            ->with('employee:id,emp_code,name')
            ->orderBy('id')
            ->get();

        return view('admin.hr.payroll-show', [
            'run' => $payrollRun,
            'details' => $details,
        ]);
    }

    /**
     * Download payroll summary PDF.
     */
    public function downloadPdf(Request $request, PayrollRun $payrollRun): Response
    {
        $this->authorize('view', $payrollRun);
        if ($payrollRun->status !== 'processed' && $payrollRun->status !== 'approved' && $payrollRun->status !== 'paid') {
            abort(404);
        }

        return $this->pdfGenerator->downloadOrGenerate(
            PdfDocumentType::PayrollSummary,
            $payrollRun,
            $request->user()?->id
        );
    }

    public function create(): View
    {
        $this->authorize('create', PayrollRun::class);

        return view('admin.hr.payroll-create');
    }

    public function store(StorePayrollRunRequest $request): JsonResponse
    {
        try {
            $v = $request->validated();
            PayrollRun::query()->create([
                'period_year' => $v['period_year'],
                'period_month' => $v['period_month'],
                'status' => 'draft',
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Payroll run created.',
            ], 201);
        } catch (Throwable $e) {
            Log::error('PayrollRunController@store failed', ['message' => $e->getMessage()]);

            return response()->json([
                'status' => false,
                'message' => 'Could not create payroll run.',
            ], 500);
        }
    }

    public function process(PayrollRun $payrollRun): JsonResponse
    {
        $this->authorize('update', $payrollRun);

        $user = request()->user();
        if ($user === null) {
            abort(403);
        }

        try {
            $this->payrollService->process($payrollRun, $user);

            return response()->json([
                'status' => true,
                'message' => 'Payroll processed with PF/ESI deductions and journal posted.',
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (Throwable $e) {
            Log::error('PayrollRunController@process failed', ['message' => $e->getMessage()]);

            return response()->json([
                'status' => false,
                'message' => 'Could not process payroll run.',
            ], 500);
        }
    }

    public function approve(PayrollRun $payrollRun): JsonResponse
    {
        $this->authorize('approve', $payrollRun);
        $user = request()->user();
        if ($user === null) {
            abort(403);
        }

        try {
            $this->payrollService->approve($payrollRun, $user);

            return response()->json([
                'status' => true,
                'message' => 'Payroll approved. Payslips generated and sent.',
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 422);
        } catch (Throwable $e) {
            Log::error('PayrollRunController@approve failed', ['message' => $e->getMessage()]);

            return response()->json(['status' => false, 'message' => 'Could not approve payroll run.'], 500);
        }
    }

    public function lockAttendance(PayrollRun $payrollRun): JsonResponse
    {
        $this->authorize('lockAttendance', $payrollRun);

        if ($payrollRun->status !== 'draft') {
            return response()->json([
                'status' => false,
                'message' => 'Attendance can only be locked on draft payroll runs.',
            ], 422);
        }

        $payrollRun->update(['attendance_locked' => true]);

        return response()->json([
            'status' => true,
            'message' => 'Attendance locked for this payroll period.',
        ]);
    }

    public function markPaid(PayrollRun $payrollRun): JsonResponse
    {
        $this->authorize('markPaid', $payrollRun);

        if ($payrollRun->status !== 'approved') {
            return response()->json([
                'status' => false,
                'message' => 'Only HR-approved payroll runs can be marked paid.',
            ], 422);
        }

        PayrollDetail::query()
            ->where('payroll_run_id', $payrollRun->id)
            ->update(['payment_status' => 'PAID']);

        $payrollRun->update(['paid_at' => now(), 'status' => 'paid']);

        return response()->json([
            'status' => true,
            'message' => 'All payslips marked as PAID.',
        ]);
    }

    public function exportBankFile(Request $request, PayrollRun $payrollRun): StreamedResponse
    {
        $this->authorize('view', $payrollRun);

        try {
            return $this->bankExport->downloadCsv(
                $payrollRun,
                (string) $request->input('format', config('payroll.bank_export_format', 'icici'))
            );
        } catch (\InvalidArgumentException $e) {
            abort(422, $e->getMessage());
        }
    }

    public function exportPfEcr(PayrollRun $payrollRun): StreamedResponse
    {
        $this->authorize('view', $payrollRun);

        try {
            return $this->statutoryExport->downloadPfEcr($payrollRun);
        } catch (\InvalidArgumentException $e) {
            abort(422, $e->getMessage());
        }
    }

    public function exportEsi(PayrollRun $payrollRun): StreamedResponse
    {
        $this->authorize('view', $payrollRun);

        try {
            return $this->statutoryExport->downloadEsi($payrollRun);
        } catch (\InvalidArgumentException $e) {
            abort(422, $e->getMessage());
        }
    }

    public function exportPt(PayrollRun $payrollRun): StreamedResponse
    {
        $this->authorize('view', $payrollRun);

        try {
            return $this->statutoryExport->downloadProfessionalTax($payrollRun);
        } catch (\InvalidArgumentException $e) {
            abort(422, $e->getMessage());
        }
    }
}
