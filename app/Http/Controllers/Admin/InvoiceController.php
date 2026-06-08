<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Services\InvoicePdfService;
use Illuminate\Http\Response;

/**
 * Sales invoice PDF download.
 */
class InvoiceController extends Controller
{
    public function __construct(
        protected InvoicePdfService $pdf
    ) {}

    public function downloadPdf(Invoice $invoice): Response
    {
        $this->authorize('view', $invoice);
        if ($invoice->status !== 'posted') {
            abort(404);
        }

        return $this->pdf->download($invoice);
    }
}
