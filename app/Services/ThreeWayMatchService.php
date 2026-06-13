<?php

namespace App\Services;

use App\Models\GoodsReceipt;
use App\Models\PurchaseOrder;
use App\Models\VendorInvoice;

/**
 * Line-level PO → GRN → vendor invoice qty and amount match (SRS UC 22.1 step 9).
 */
class ThreeWayMatchService
{
    public function evaluate(VendorInvoice $invoice): void
    {
        $invoice->loadMissing('vendorPayable.goodsReceipt.purchaseOrder.lines', 'vendorPayable.goodsReceipt.lines');
        $payable = $invoice->vendorPayable;
        if ($payable === null) {
            return;
        }

        $grn = $payable->goodsReceipt;
        $po = $grn?->purchaseOrder;
        $tolerance = (string) config('eway.match_tolerance', '1.00');
        $vendorAmount = (string) $invoice->amount;
        $grnAmount = (string) ($invoice->grn_amount ?? $payable->amount);
        $poAmount = $invoice->po_amount !== null ? (string) $invoice->po_amount : null;

        $lineResults = $grn !== null && $po !== null
            ? $this->validateLineQuantities($grn, $po)
            : ['ok' => true, 'details' => []];

        $grnOk = $this->withinTolerance($vendorAmount, $grnAmount, $tolerance);
        $poOk = $poAmount === null || $this->withinTolerance($vendorAmount, $poAmount, $tolerance);
        $qtyOk = $lineResults['ok'];

        if ($grnOk && $poOk && $qtyOk) {
            $invoice->update([
                'match_status' => 'matched',
                'status' => 'matched',
                'match_notes' => $this->formatMatchNotes(true, $lineResults['details']),
            ]);

            return;
        }

        $notes = [];
        if (! $qtyOk) {
            $notes[] = 'Line quantity mismatch between PO and GRN accepted quantities.';
        }
        if (! $grnOk) {
            $notes[] = 'Amount differs from GRN payable '.$grnAmount.'.';
        }
        if (! $poOk && $poAmount !== null) {
            $notes[] = 'Amount differs from PO total '.$poAmount.'.';
        }

        $invoice->update([
            'match_status' => 'variance',
            'status' => 'variance',
            'match_notes' => $this->formatMatchNotes(false, $lineResults['details'], $notes),
        ]);
    }

    /**
     * @return array{ok: bool, details: list<array<string, mixed>>}
     */
    protected function validateLineQuantities(GoodsReceipt $grn, PurchaseOrder $po): array
    {
        $grn->loadMissing('lines');
        $po->loadMissing('lines');
        $details = [];
        $allOk = true;

        foreach ($grn->lines as $grnLine) {
            $poLine = $po->lines->firstWhere('item_id', $grnLine->item_id);
            $poQty = $poLine !== null ? (string) $poLine->quantity : '0';
            $grnReceived = (string) $grnLine->quantity;
            $accepted = (string) ($grnLine->accepted_qty ?? $grnLine->quantity);

            $lineOk = $poLine !== null
                && bccomp($grnReceived, $poQty, 4) <= 0
                && bccomp($accepted, $grnReceived, 4) <= 0;

            if (! $lineOk) {
                $allOk = false;
            }

            $details[] = [
                'item_id' => $grnLine->item_id,
                'po_qty' => $poQty,
                'grn_qty' => $grnReceived,
                'accepted_qty' => $accepted,
                'ok' => $lineOk,
            ];
        }

        return ['ok' => $allOk, 'details' => $details];
    }

    /**
     * @param  list<array<string, mixed>>  $lineDetails
     * @param  list<string>  $extraNotes
     */
    protected function formatMatchNotes(bool $matched, array $lineDetails, array $extraNotes = []): string
    {
        $parts = $matched
            ? ['3-way match successful: PO qty → GRN accepted → vendor invoice amount.']
            : $extraNotes;

        foreach ($lineDetails as $row) {
            $status = ($row['ok'] ?? false) ? 'OK' : 'VARIANCE';
            $parts[] = sprintf(
                'Item #%s: PO %s / GRN %s / Accepted %s [%s]',
                $row['item_id'] ?? '?',
                $row['po_qty'] ?? '0',
                $row['grn_qty'] ?? '0',
                $row['accepted_qty'] ?? '0',
                $status
            );
        }

        return implode(' ', $parts);
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
