<?php

namespace App\Services\Pdf;

use App\Enums\PdfDocumentType;
use App\Jobs\GenerateDocumentPdfJob;
use App\Models\Company;
use App\Models\GeneratedDocument;
use App\Models\GoodsReceipt;
use App\Models\Invoice;
use App\Models\PayrollDetail;
use App\Models\PayrollRun;
use App\Models\ProductionOrder;
use App\Models\PurchaseOrder;
use App\Models\SalesDispatchChallan;
use App\Models\SalesQuotation;
use App\Models\StockLedger;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorPayable;
use App\Models\Payment;
use App\Services\AuditLogService;
use App\Services\GstrExportService;
use App\Support\BarcodeSvg;
use App\Support\NumberToWords;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

/**
 * Central PDF generation pipeline per SRS addendum chapter 21.
 */
class PdfGeneratorService
{
    public function __construct(
        protected AuditLogService $auditLog,
        protected GstrExportService $gstrExport
    ) {}

    /**
     * Dispatch PDF generation to the queue.
     *
     * @param  array<string, mixed>  $meta
     */
    public function queue(PdfDocumentType $type, Model $model, ?int $userId = null, array $meta = []): void
    {
        GenerateDocumentPdfJob::dispatch(
            $type,
            $model::class,
            (int) $model->getKey(),
            $userId,
            $meta
        );
    }

    /**
     * Render, store, and record a PDF synchronously.
     *
     * @param  array<string, mixed>  $meta
     */
    public function generate(PdfDocumentType $type, Model $model, ?int $userId = null, array $meta = []): GeneratedDocument
    {
        $metaHash = $this->metaHash($type, $model, $meta);
        $this->supersedeExisting($type, $model, $metaHash);

        $company = Company::query()->orderBy('id')->first();
        $viewData = $this->buildViewData($type, $model, $company, $meta);
        $downloadName = $this->downloadName($type, $model, $meta);
        $relativePath = $this->storageRelativePath($type, $downloadName);

        $pdf = Pdf::loadView($type->viewName(), $viewData)
            ->setPaper('a4', $type->orientation())
            ->setOption('defaultFont', 'DejaVu Sans');

        if ($type === PdfDocumentType::Payslip && isset($viewData['detail'])) {
            /** @var PayrollDetail $detail */
            $detail = $viewData['detail'];
            $employee = $detail->employee;
            $password = $this->payslipPassword($employee);
            if ($password !== null) {
                $pdf->setEncryption($password, $password, ['print', 'copy']);
            }
        }

        $disk = (string) config('pdf.disk', 'local');
        Storage::disk($disk)->put($relativePath, $pdf->output());

        $expiresAt = now()->addHours((int) config('pdf.signed_url_hours', 24));

        $document = GeneratedDocument::query()->create([
            'document_type' => $type,
            'documentable_type' => $model::class,
            'documentable_id' => (int) $model->getKey(),
            'module' => $type->module(),
            'file_path' => $relativePath,
            'download_name' => $downloadName,
            'meta' => $meta !== [] ? $meta : null,
            'meta_hash' => $metaHash,
            'generated_by' => $userId,
            'expires_at' => $expiresAt,
            'is_active' => true,
        ]);

        $user = $userId !== null ? User::query()->find($userId) : null;
        $this->auditLog->record(
            'pdf.generated',
            'Generated '.$type->value.' PDF for '.$model::class.' #'.$model->getKey(),
            $document,
            $user
        );

        return $document;
    }

    /**
     * Return latest active generated document for a model and type.
     *
     * @param  array<string, mixed>  $meta
     */
    public function latest(PdfDocumentType $type, Model $model, array $meta = []): ?GeneratedDocument
    {
        $metaHash = $this->metaHash($type, $model, $meta);

        return GeneratedDocument::query()
            ->where('document_type', $type)
            ->where('documentable_type', $model::class)
            ->where('documentable_id', $model->getKey())
            ->where('is_active', true)
            ->when($metaHash !== null, fn ($q) => $q->where('meta_hash', $metaHash))
            ->latest('id')
            ->first();
    }

    /**
     * Download stored PDF or generate on demand (admin fallback).
     *
     * @param  array<string, mixed>  $meta
     */
    public function downloadOrGenerate(PdfDocumentType $type, Model $model, ?int $userId = null, array $meta = []): Response
    {
        $document = $this->latest($type, $model, $meta);
        $disk = (string) config('pdf.disk', 'local');

        if ($document === null || ! Storage::disk($disk)->exists($document->file_path)) {
            $document = $this->generate($type, $model, $userId, $meta);
        }

        return $this->streamStored($document);
    }

    /**
     * Temporary signed download URL (SRS §21.13).
     */
    public function signedDownloadUrl(GeneratedDocument $document): string
    {
        if ($document->isExpired()) {
            $document->update([
                'expires_at' => now()->addHours((int) config('pdf.signed_url_hours', 24)),
            ]);
        }

        return URL::temporarySignedRoute(
            'documents.download',
            $document->expires_at ?? now()->addHours((int) config('pdf.signed_url_hours', 24)),
            ['generatedDocument' => $document->id]
        );
    }

    /**
     * Stream a stored PDF via signed route.
     */
    public function streamStored(GeneratedDocument $document): Response
    {
        $disk = (string) config('pdf.disk', 'local');

        if (! Storage::disk($disk)->exists($document->file_path)) {
            abort(404, 'PDF file not found.');
        }

        $content = Storage::disk($disk)->get($document->file_path);

        return response($content, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$document->download_name.'"',
        ]);
    }

    /**
     * @param  array<string, mixed>  $meta
     * @return array<string, mixed>
     */
    protected function buildViewData(PdfDocumentType $type, Model $model, ?Company $company, array $meta): array
    {
        $base = [
            'company' => $company,
            'docTitle' => $type->title(),
            'generatedAt' => now()->format('d M Y H:i'),
            'watermark' => null,
        ];

        return match ($type) {
            PdfDocumentType::TaxInvoice => $this->taxInvoiceData($model, $company, $base),
            PdfDocumentType::PurchaseOrder => $this->purchaseOrderData($model, $base),
            PdfDocumentType::Grn => $this->grnData($model, $base),
            PdfDocumentType::Quotation => $this->quotationData($model, $base),
            PdfDocumentType::DeliveryChallan => $this->deliveryChallanData($model, $base),
            PdfDocumentType::Payslip => $this->payslipData($model, $base),
            PdfDocumentType::PayrollSummary => $this->payrollSummaryData($model, $base),
            PdfDocumentType::StockLedger => $this->stockLedgerData($meta, $base),
            PdfDocumentType::Gstr1 => $this->gstr1Data($meta, $base),
            PdfDocumentType::Gstr3b => $this->gstr3bData($meta, $base),
            PdfDocumentType::VendorStatement => $this->vendorStatementData($model, $meta, $base),
            PdfDocumentType::ProductionOrder => $this->productionOrderData($model, $base),
        };
    }

    /**
     * @param  array<string, mixed>  $base
     * @return array<string, mixed>
     */
    protected function taxInvoiceData(Model $model, ?Company $company, array $base): array
    {
        /** @var Invoice $invoice */
        $invoice = $model;
        $invoice->loadMissing(['customer', 'invoiceItems.item', 'salesOrder']);

        $notValidated = $company?->einvoice_enabled
            && $invoice->customer?->gstin
            && $invoice->irn === null;

        $watermark = $notValidated ? 'NOT VALIDATED' : $this->statusWatermark($invoice->status);

        return array_merge($base, [
            'invoice' => $invoice,
            'customer' => $invoice->customer,
            'lines' => $invoice->invoiceItems,
            'amountInWords' => NumberToWords::rupees((string) $invoice->total_amount),
            'watermark' => $watermark,
        ]);
    }

    /**
     * @param  array<string, mixed>  $base
     * @return array<string, mixed>
     */
    protected function purchaseOrderData(Model $model, array $base): array
    {
        /** @var PurchaseOrder $po */
        $po = $model;
        $po->loadMissing(['vendor', 'warehouse', 'lines.item', 'creator']);

        return array_merge($base, [
            'purchaseOrder' => $po,
            'vendor' => $po->vendor,
            'warehouse' => $po->warehouse,
            'lines' => $po->lines,
            'amountInWords' => NumberToWords::rupees((string) $po->total_amount),
            'watermark' => $this->statusWatermark($po->status),
        ]);
    }

    /**
     * @param  array<string, mixed>  $base
     * @return array<string, mixed>
     */
    protected function grnData(Model $model, array $base): array
    {
        /** @var GoodsReceipt $grn */
        $grn = $model;
        $grn->loadMissing(['vendor', 'warehouse', 'purchaseOrder', 'lines.item', 'creator']);

        return array_merge($base, [
            'grn' => $grn,
            'vendor' => $grn->vendor,
            'warehouse' => $grn->warehouse,
            'purchaseOrder' => $grn->purchaseOrder,
            'lines' => $grn->lines,
            'watermark' => $this->statusWatermark($grn->status),
            'barcodeSvg' => BarcodeSvg::code39($grn->grn_number),
        ]);
    }

    /**
     * @param  array<string, mixed>  $base
     * @return array<string, mixed>
     */
    protected function quotationData(Model $model, array $base): array
    {
        /** @var SalesQuotation $quotation */
        $quotation = $model;
        $quotation->loadMissing(['customer', 'lines.item']);

        $total = '0.00';
        foreach ($quotation->lines as $line) {
            $lineTotal = bcmul((string) $line->quantity, (string) $line->unit_price, 2);
            $total = bcadd($total, $lineTotal, 2);
        }

        return array_merge($base, [
            'quotation' => $quotation,
            'customer' => $quotation->customer,
            'lines' => $quotation->lines,
            'totalAmount' => $total,
            'amountInWords' => NumberToWords::rupees($total),
            'watermark' => in_array($quotation->status, ['draft', 'expired'], true) ? strtoupper($quotation->status) : 'INDICATIVE PRICING',
        ]);
    }

    /**
     * @param  array<string, mixed>  $base
     * @return array<string, mixed>
     */
    protected function deliveryChallanData(Model $model, array $base): array
    {
        /** @var SalesDispatchChallan $challan */
        $challan = $model;
        $challan->loadMissing(['customer', 'warehouse', 'salesOrder.lines.item', 'salesOrder.postedInvoice']);

        return array_merge($base, [
            'challan' => $challan,
            'order' => $challan->salesOrder,
            'customer' => $challan->customer,
            'warehouse' => $challan->warehouse,
            'lines' => $challan->salesOrder?->lines ?? collect(),
            'invoice' => $challan->salesOrder?->postedInvoice,
        ]);
    }

    /**
     * @param  array<string, mixed>  $base
     * @return array<string, mixed>
     */
    protected function payslipData(Model $model, array $base): array
    {
        /** @var PayrollDetail $detail */
        $detail = $model;
        $detail->loadMissing(['employee', 'payrollRun']);

        $employee = $detail->employee;
        $run = $detail->payrollRun;
        $periodLabel = $run !== null
            ? Carbon::create((int) $run->period_year, (int) $run->period_month, 1)->format('F Y')
            : '';

        $settings = \App\Models\PayrollSetting::current();
        $employerPf = (string) ($detail->pf_employer ?? '0.00');
        $employerEsi = (string) ($detail->esi_employer ?? '0.00');

        $breakdown = is_array($detail->earnings_breakdown) ? $detail->earnings_breakdown : [];
        $earningLines = [
            ['label' => 'Basic Salary', 'amount' => (string) ($breakdown['basic'] ?? $detail->basic_salary)],
        ];
        foreach ($breakdown['allowances'] ?? [] as $line) {
            if (! is_array($line) || bccomp((string) ($line['amount'] ?? '0'), '0', 2) <= 0) {
                continue;
            }
            $earningLines[] = [
                'label' => (string) ($line['name'] ?? $line['code'] ?? 'Allowance'),
                'amount' => (string) $line['amount'],
            ];
        }
        if (count($earningLines) === 1 && bccomp((string) $detail->hra, '0', 2) > 0) {
            $earningLines[] = ['label' => 'HRA', 'amount' => (string) $detail->hra];
        }

        return array_merge($base, [
            'detail' => $detail,
            'employee' => $employee,
            'run' => $run,
            'periodLabel' => $periodLabel,
            'earningLines' => $earningLines,
            'pfEmployeeLabel' => $this->formatRatePercent((string) $settings->pf_employee_rate),
            'esiEmployeeLabel' => $this->formatRatePercent((string) $settings->esi_employee_rate),
            'employerPf' => $employerPf,
            'employerEsi' => $employerEsi,
            'netPayWords' => NumberToWords::rupees((string) $detail->net_salary),
        ]);
    }

    /**
     * @param  array<string, mixed>  $base
     * @return array<string, mixed>
     */
    protected function payrollSummaryData(Model $model, array $base): array
    {
        /** @var PayrollRun $run */
        $run = $model;
        $details = PayrollDetail::query()
            ->where('payroll_run_id', $run->id)
            ->with('employee')
            ->orderBy('id')
            ->get();

        $totals = [
            'gross' => '0.00',
            'pf' => '0.00',
            'esi' => '0.00',
            'pt' => '0.00',
            'net' => '0.00',
        ];

        foreach ($details as $row) {
            $totals['gross'] = bcadd($totals['gross'], (string) $row->gross_salary, 2);
            $totals['pf'] = bcadd($totals['pf'], (string) $row->pf_deduction, 2);
            $totals['esi'] = bcadd($totals['esi'], (string) $row->esi_deduction, 2);
            $totals['pt'] = bcadd($totals['pt'], (string) $row->professional_tax, 2);
            $totals['net'] = bcadd($totals['net'], (string) $row->net_salary, 2);
        }

        $periodLabel = Carbon::create((int) $run->period_year, (int) $run->period_month, 1)->format('F Y');

        return array_merge($base, [
            'run' => $run,
            'details' => $details,
            'totals' => $totals,
            'periodLabel' => $periodLabel,
        ]);
    }

    /**
     * @param  array<string, mixed>  $meta
     * @param  array<string, mixed>  $base
     * @return array<string, mixed>
     */
    protected function stockLedgerData(array $meta, array $base): array
    {
        $itemId = (int) ($meta['item_id'] ?? 0);
        $warehouseId = isset($meta['warehouse_id']) ? (int) $meta['warehouse_id'] : null;
        $dateFrom = (string) ($meta['date_from'] ?? now()->startOfMonth()->toDateString());
        $dateTo = (string) ($meta['date_to'] ?? now()->toDateString());

        $query = StockLedger::query()
            ->with(['item:id,sku,name,uom', 'warehouse:id,code,name'])
            ->where('item_id', $itemId)
            ->whereDate('created_at', '>=', $dateFrom)
            ->whereDate('created_at', '<=', $dateTo)
            ->orderBy('created_at')
            ->orderBy('id');

        if ($warehouseId !== null) {
            $query->where('warehouse_id', $warehouseId);
        }

        return array_merge($base, [
            'rows' => $query->get(),
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'item' => \App\Models\Item::query()->find($itemId),
            'warehouse' => $warehouseId ? \App\Models\Warehouse::query()->find($warehouseId) : null,
        ]);
    }

    /**
     * @param  array<string, mixed>  $meta
     * @param  array<string, mixed>  $base
     * @return array<string, mixed>
     */
    protected function gstr1Data(array $meta, array $base): array
    {
        $year = (int) ($meta['year'] ?? now()->year);
        $month = (int) ($meta['month'] ?? now()->month);
        $data = $this->gstrExport->reportData($year, $month);

        return array_merge($base, [
            'year' => $year,
            'month' => $month,
            'invoices' => $data['invoices'],
            'totals' => $data['totals'],
        ]);
    }

    /**
     * @param  array<string, mixed>  $meta
     * @param  array<string, mixed>  $base
     * @return array<string, mixed>
     */
    protected function gstr3bData(array $meta, array $base): array
    {
        $year = (int) ($meta['year'] ?? now()->year);
        $month = (int) ($meta['month'] ?? now()->month);
        $data = $this->gstrExport->reportData($year, $month);

        return array_merge($base, [
            'year' => $year,
            'month' => $month,
            'totals' => $data['totals'],
        ]);
    }

    /**
     * @param  array<string, mixed>  $meta
     * @param  array<string, mixed>  $base
     * @return array<string, mixed>
     */
    protected function vendorStatementData(Model $model, array $meta, array $base): array
    {
        /** @var Vendor $vendor */
        $vendor = $model;
        $dateFrom = (string) ($meta['date_from'] ?? now()->startOfYear()->toDateString());
        $dateTo = (string) ($meta['date_to'] ?? now()->toDateString());

        $entries = [];
        $balance = '0.00';

        $pos = PurchaseOrder::query()
            ->where('vendor_id', $vendor->id)
            ->whereBetween('order_date', [$dateFrom, $dateTo])
            ->whereIn('status', ['approved', 'sent'])
            ->orderBy('order_date')
            ->get(['id', 'po_number', 'order_date', 'total_amount', 'status']);

        foreach ($pos as $po) {
            $entries[] = [
                'date' => $po->order_date?->format('d-m-Y'),
                'type' => 'PO',
                'reference' => $po->po_number,
                'debit' => '0.00',
                'credit' => '0.00',
                'balance' => $balance,
            ];
        }

        $payables = VendorPayable::query()
            ->where('vendor_id', $vendor->id)
            ->whereHas('goodsReceipt', fn ($q) => $q->whereBetween('received_at', [$dateFrom, $dateTo]))
            ->with('goodsReceipt:id,grn_number,received_at')
            ->orderBy('id')
            ->get();

        foreach ($payables as $payable) {
            $balance = bcadd($balance, (string) $payable->amount, 2);
            $entries[] = [
                'date' => $payable->goodsReceipt?->received_at?->format('d-m-Y'),
                'type' => 'GRN',
                'reference' => $payable->goodsReceipt?->grn_number ?? '—',
                'debit' => (string) $payable->amount,
                'credit' => '0.00',
                'balance' => $balance,
            ];
        }

        $payments = Payment::query()
            ->where('vendor_id', $vendor->id)
            ->where('payment_type', 'vendor_payment')
            ->whereBetween('payment_date', [$dateFrom, $dateTo])
            ->orderBy('payment_date')
            ->get();

        foreach ($payments as $payment) {
            $balance = bcsub($balance, (string) $payment->amount, 2);
            $entries[] = [
                'date' => $payment->payment_date?->format('d-m-Y'),
                'type' => 'Payment',
                'reference' => $payment->payment_number,
                'debit' => '0.00',
                'credit' => (string) $payment->amount,
                'balance' => $balance,
            ];
        }

        usort($entries, fn (array $a, array $b): int => strcmp((string) $a['date'], (string) $b['date']));

        return array_merge($base, [
            'vendor' => $vendor,
            'entries' => $entries,
            'closingBalance' => $balance,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
        ]);
    }

    /**
     * @param  array<string, mixed>  $base
     * @return array<string, mixed>
     */
    protected function productionOrderData(Model $model, array $base): array
    {
        /** @var ProductionOrder $order */
        $order = $model;
        $order->loadMissing(['item', 'warehouse', 'billOfMaterial.lines.componentItem', 'creator']);

        $bomLines = collect();
        if ($order->billOfMaterial !== null) {
            $bomLines = $order->billOfMaterial->lines->load('componentItem');
        }

        return array_merge($base, [
            'order' => $order,
            'item' => $order->item,
            'warehouse' => $order->warehouse,
            'bomLines' => $bomLines,
            'watermark' => $this->statusWatermark($order->status),
        ]);
    }

    protected function statusWatermark(?string $status): ?string
    {
        if ($status === null) {
            return null;
        }

        return match ($status) {
            'draft' => 'DRAFT',
            'cancelled' => 'CANCELLED',
            default => null,
        };
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    protected function downloadName(PdfDocumentType $type, Model $model, array $meta): string
    {
        return match ($type) {
            PdfDocumentType::TaxInvoice => 'invoice-'.($model->invoice_number ?? $model->getKey()).'.pdf',
            PdfDocumentType::PurchaseOrder => 'po-'.($model->po_number ?? $model->getKey()).'.pdf',
            PdfDocumentType::Grn => 'grn-'.($model->grn_number ?? $model->getKey()).'.pdf',
            PdfDocumentType::Quotation => 'quote-'.($model->quote_number ?? $model->getKey()).'.pdf',
            PdfDocumentType::DeliveryChallan => 'challan-'.($model->challan_number ?? $model->getKey()).'.pdf',
            PdfDocumentType::Payslip => sprintf('payslip-%d.pdf', $model->getKey()),
            PdfDocumentType::PayrollSummary => sprintf(
                'payroll-summary-%s-%02d.pdf',
                $model->period_year,
                $model->period_month
            ),
            PdfDocumentType::StockLedger => 'stock-ledger-'.($meta['item_id'] ?? 'all').'.pdf',
            PdfDocumentType::Gstr1 => sprintf('gstr1-%04d-%02d.pdf', $meta['year'] ?? now()->year, $meta['month'] ?? now()->month),
            PdfDocumentType::Gstr3b => sprintf('gstr3b-%04d-%02d.pdf', $meta['year'] ?? now()->year, $meta['month'] ?? now()->month),
            PdfDocumentType::VendorStatement => 'vendor-statement-'.($model->id ?? 'vendor').'.pdf',
            PdfDocumentType::ProductionOrder => 'production-'.($model->wo_number ?? $model->getKey()).'.pdf',
        };
    }

    protected function storageRelativePath(PdfDocumentType $type, string $downloadName): string
    {
        $base = (string) config('pdf.storage_path', 'documents');
        $folder = $type->module().'/'.now()->format('Y-m');
        $filename = Str::uuid()->toString().'.pdf';

        return $base.'/'.$folder.'/'.$filename;
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    protected function metaHash(PdfDocumentType $type, Model $model, array $meta): ?string
    {
        if ($meta === []) {
            return null;
        }

        return hash('sha256', $type->value.'|'.$model::class.'|'.$model->getKey().'|'.json_encode($meta));
    }

    protected function supersedeExisting(PdfDocumentType $type, Model $model, ?string $metaHash): void
    {
        $query = GeneratedDocument::query()
            ->where('document_type', $type)
            ->where('documentable_type', $model::class)
            ->where('documentable_id', $model->getKey())
            ->where('is_active', true);

        if ($metaHash !== null) {
            $query->where('meta_hash', $metaHash);
        } else {
            $query->whereNull('meta_hash');
        }

        $query->update(['is_active' => false, 'expires_at' => now()]);
    }

    protected function formatRatePercent(string $rate): string
    {
        $pct = (float) $rate * 100;

        return rtrim(rtrim(number_format($pct, 2), '0'), '.').'%';
    }

    /**
     * Payslip PDF password: DDMMYYYY from date of birth, else last 4 of employee code (SRS §21.7).
     */
    protected function payslipPassword(?\App\Models\Employee $employee): ?string
    {
        if ($employee === null) {
            return null;
        }
        if ($employee->date_of_birth !== null) {
            return $employee->date_of_birth->format('dmY');
        }
        $code = preg_replace('/\D/', '', (string) $employee->emp_code) ?? '';

        return strlen($code) >= 4 ? substr($code, -4) : ($code !== '' ? $code : null);
    }
}
