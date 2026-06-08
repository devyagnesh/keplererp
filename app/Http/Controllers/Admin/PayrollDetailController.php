<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PdfDocumentType;
use App\Http\Controllers\Controller;
use App\Models\PayrollDetail;
use App\Services\Pdf\PdfGeneratorService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Per-employee payslip PDF download (HR).
 */
class PayrollDetailController extends Controller
{
    public function __construct(
        protected PdfGeneratorService $pdfGenerator
    ) {}

    public function downloadPdf(Request $request, PayrollDetail $payrollDetail): Response
    {
        $payrollDetail->loadMissing('payrollRun');
        $run = $payrollDetail->payrollRun;
        if ($run === null || $run->status !== 'processed') {
            abort(404);
        }

        $this->authorize('view', $run);

        return $this->pdfGenerator->downloadOrGenerate(
            PdfDocumentType::Payslip,
            $payrollDetail,
            $request->user()?->id
        );
    }
}
