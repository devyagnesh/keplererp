<?php

namespace App\Services;

use App\Models\Company;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderLine;
use App\Models\PurchaseRequisition;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Throwable;

/**
 * Convert approved PR to draft PO (SRS procurement step 4).
 */
class PurchaseRequisitionConversionService
{
    public function __construct(
        protected GstCalculationService $gst
    ) {}

    /**
     * @return PurchaseOrder
     *
     * @throws Throwable
     */
    public function convertToPurchaseOrder(PurchaseRequisition $pr, User $user, int $vendorId): PurchaseOrder
    {
        if ($pr->status !== 'approved') {
            throw new InvalidArgumentException('Only approved requisitions can be converted to a purchase order.');
        }

        return DB::transaction(function () use ($pr, $user, $vendorId): PurchaseOrder {
            $pr->load(['lines.item', 'lines']);
            $company = Company::query()->orderBy('id')->firstOrFail();
            $vendor = \App\Models\Vendor::query()->findOrFail($vendorId);

            $max = (int) PurchaseOrder::withTrashed()->max('id');
            $poNumber = 'PO-'.str_pad((string) ($max + 1), 5, '0', STR_PAD_LEFT);

            $po = PurchaseOrder::query()->create([
                'po_number' => $poNumber,
                'pr_id' => $pr->id,
                'vendor_id' => $vendorId,
                'warehouse_id' => $pr->warehouse_id,
                'order_date' => now()->toDateString(),
                'expected_delivery' => $pr->required_date ?? now()->addDays(7)->toDateString(),
                'payment_terms_days' => 30,
                'status' => 'draft',
                'created_by' => $user->id,
                'notes' => $pr->notes,
            ]);

            $subtotal = '0.00';
            $taxable = '0.00';
            $cgst = '0.00';
            $sgst = '0.00';
            $igst = '0.00';

            foreach ($pr->lines as $line) {
                $unitCost = (string) ($line->estimated_price ?? $line->item?->purchase_price ?? '0');
                $qty = (string) $line->quantity;
                $lineTaxable = bcmul($unitCost, $qty, 2);
                $rate = (string) ($line->item?->gst_rate ?? '18');
                $split = $this->gst->splitLineTax($lineTaxable, $rate, $company->state_code, $vendor->state_code);
                $lineTotal = bcadd($lineTaxable, bcadd(bcadd($split['cgst'], $split['sgst'], 2), $split['igst'], 2), 2);

                PurchaseOrderLine::query()->create([
                    'purchase_order_id' => $po->id,
                    'item_id' => $line->item_id,
                    'quantity' => $qty,
                    'unit_cost' => $unitCost,
                    'gst_rate' => $rate,
                    'taxable_value' => $lineTaxable,
                    'cgst' => $split['cgst'],
                    'sgst' => $split['sgst'],
                    'igst' => $split['igst'],
                    'line_total' => $lineTotal,
                ]);

                $subtotal = bcadd($subtotal, $lineTaxable, 2);
                $taxable = bcadd($taxable, $lineTaxable, 2);
                $cgst = bcadd($cgst, $split['cgst'], 2);
                $sgst = bcadd($sgst, $split['sgst'], 2);
                $igst = bcadd($igst, $split['igst'], 2);
            }

            $total = bcadd(bcadd(bcadd($taxable, $cgst, 2), $sgst, 2), $igst, 2);
            $po->update([
                'subtotal' => $subtotal,
                'taxable_amount' => $taxable,
                'cgst_amount' => $cgst,
                'sgst_amount' => $sgst,
                'igst_amount' => $igst,
                'total_amount' => $total,
            ]);

            $pr->update(['status' => 'converted']);

            return $po->fresh(['lines']);
        });
    }
}
