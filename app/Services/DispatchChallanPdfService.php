<?php

namespace App\Services;

use App\Enums\PdfDocumentType;
use App\Models\SalesDispatchChallan;
use App\Services\Pdf\PdfGeneratorService;
use Illuminate\Http\Response;

/**
 * Renders dispatch challans as PDF via central PdfGeneratorService.
 */
class DispatchChallanPdfService
{
    public function __construct(
        protected PdfGeneratorService $pdfGenerator
    ) {}

    public function download(SalesDispatchChallan $challan): Response
    {
        return $this->pdfGenerator->downloadOrGenerate(
            PdfDocumentType::DeliveryChallan,
            $challan,
            auth()->id()
        );
    }
}
