<?php

namespace App\Services;

use App\Models\VendorInvoice;

/**
 * Compares vendor invoice amount to GRN payable (and PO total when available).
 */
class ThreeWayMatchService
{
    public function evaluate(VendorInvoice $invoice): void
    {
        $invoice->loadMissing('vendorPayable.goodsReceipt.purchaseOrder');
        $payable = $invoice->vendorPayable;
        if ($payable === null) {
            return;
        }

        $tolerance = (string) config('eway.match_tolerance', '1.00');
        $vendorAmount = (string) $invoice->amount;
        $grnAmount = (string) ($invoice->grn_amount ?? $payable->amount);
        $poAmount = $invoice->po_amount !== null ? (string) $invoice->po_amount : null;

        $grnOk = $this->withinTolerance($vendorAmount, $grnAmount, $tolerance);
        $poOk = $poAmount === null || $this->withinTolerance($vendorAmount, $poAmount, $tolerance);

        if ($grnOk && $poOk) {
            $invoice->update([
                'match_status' => 'matched',
                'status' => 'matched',
                'match_notes' => 'Vendor invoice matches GRN payable'.($poAmount !== null ? ' and PO total' : '').'.',
            ]);

            return;
        }

        $notes = [];
        if (! $grnOk) {
            $notes[] = 'Amount differs from GRN payable '.$grnAmount.'.';
        }
        if (! $poOk && $poAmount !== null) {
            $notes[] = 'Amount differs from PO total '.$poAmount.'.';
        }

        $invoice->update([
            'match_status' => 'variance',
            'status' => 'variance',
            'match_notes' => implode(' ', $notes),
        ]);
    }

    protected function withinTolerance(string $a, string $b, string $tolerance): bool
    {
        $diff = bcsub($a, $b, 2);
        if (bccomp($diff, '0', 2) < 0) {
            $diff = bcmul($diff, '-1', 2);
        }

        return bccomp($diff, $tolerance, 2) <= 0;
    }
}
