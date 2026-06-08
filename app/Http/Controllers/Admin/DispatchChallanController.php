<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SalesDispatchChallan;
use App\Models\SalesOrder;
use App\Services\DispatchChallanPdfService;
use Illuminate\Http\Response;

/**
 * Dispatch challan PDF download.
 */
class DispatchChallanController extends Controller
{
    public function __construct(
        protected DispatchChallanPdfService $pdf
    ) {}

    public function downloadPdf(SalesOrder $salesOrder): Response
    {
        $this->authorize('view', $salesOrder);
        $challan = SalesDispatchChallan::query()->where('sales_order_id', $salesOrder->id)->firstOrFail();

        return $this->pdf->download($challan);
    }
}
