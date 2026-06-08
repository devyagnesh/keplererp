<?php

namespace App\Services;

use App\Enums\PdfDocumentType;
use App\Models\SalesDispatchChallan;
use App\Models\SalesOrder;
use App\Services\Pdf\PdfGeneratorService;
use Illuminate\Support\Facades\DB;

/**
 * Creates dispatch challan records when sales orders are dispatched.
 */
class DispatchChallanService
{
    public function __construct(
        protected DocumentNumberService $documentNumbers,
        protected EwayBillService $ewayBill,
        protected PdfGeneratorService $pdfGenerator
    ) {}

    public function createForSalesOrder(SalesOrder $salesOrder, ?int $userId): SalesDispatchChallan
    {
        return DB::transaction(function () use ($salesOrder, $userId): SalesDispatchChallan {
            $existing = SalesDispatchChallan::query()
                ->where('sales_order_id', $salesOrder->id)
                ->first();
            if ($existing !== null) {
                return $existing;
            }

            $salesOrder->loadMissing('customer');

            $challan = SalesDispatchChallan::query()->create([
                'challan_number' => $this->documentNumbers->next('sales_dispatch_challans', 'DC-'),
                'sales_order_id' => $salesOrder->id,
                'customer_id' => $salesOrder->customer_id,
                'warehouse_id' => $salesOrder->warehouse_id,
                'dispatched_at' => $salesOrder->dispatched_at ?? now(),
                'created_by' => $userId,
            ]);
            $this->ewayBill->generateForChallan($challan);

            $fresh = $challan->fresh();
            if ($fresh instanceof SalesDispatchChallan) {
                $this->pdfGenerator->queue(PdfDocumentType::DeliveryChallan, $fresh, $userId);
            }

            return $fresh ?? $challan->fresh();
        });
    }
}
