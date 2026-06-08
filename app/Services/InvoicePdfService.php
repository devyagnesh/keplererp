<?php

namespace App\Services;

use App\Enums\PdfDocumentType;
use App\Models\Invoice;
use App\Services\Pdf\PdfGeneratorService;
use Illuminate\Http\Response;

/**
 * Renders tax invoices as PDF (SRS sales / GST) via central PdfGeneratorService.
 */
class InvoicePdfService
{
    public function __construct(
        protected PdfGeneratorService $pdfGenerator
    ) {}

    public function download(Invoice $invoice): Response
    {
        return $this->pdfGenerator->downloadOrGenerate(
            PdfDocumentType::TaxInvoice,
            $invoice,
            auth()->id()
        );
    }
}
