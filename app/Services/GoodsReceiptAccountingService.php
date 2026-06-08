<?php

namespace App\Services;

use App\Models\GoodsReceipt;
use App\Models\VendorPayable;
use InvalidArgumentException;

/**
 * Vendor payable + inventory/AP journal for a posted GRN (linked PO required).
 */
class GoodsReceiptAccountingService
{
    public function __construct(
        protected AccountingJournalService $journal
    ) {}

    /**
     * Create vendor payable and balanced journal: Dr inventory + input GST, Cr trade payables.
     *
     * @throws \Throwable
     */
    public function postPayableAndJournal(GoodsReceipt $grn, ?int $userId): void
    {
        $po = $grn->purchaseOrder;
        if ($po === null) {
            throw new InvalidArgumentException('Purchase order is required to post GRN accounting.');
        }
        $po->loadMissing('lines');
        $grn->loadMissing('lines');

        $taxable = '0.00';
        $cgst = '0.00';
        $sgst = '0.00';
        $igst = '0.00';
        $total = '0.00';

        foreach ($grn->lines as $grnLine) {
            $poLine = $po->lines->firstWhere('item_id', $grnLine->item_id);
            if ($poLine === null) {
                throw new InvalidArgumentException('GRN item not found on linked purchase order.');
            }
            $accepted = (string) ($grnLine->accepted_qty ?? $grnLine->quantity);
            if (bccomp($accepted, '0', 4) <= 0) {
                continue;
            }
            $poQty = (string) $poLine->quantity;
            if (bccomp($poQty, '0', 4) <= 0) {
                throw new InvalidArgumentException('Invalid PO quantity for item '.$grnLine->item_id.'.');
            }
            $f = bcdiv($accepted, $poQty, 8);
            if (bccomp($f, '1', 8) > 0) {
                $f = '1';
            }
            $taxable = bcadd($taxable, bcmul((string) $poLine->taxable_value, $f, 2), 2);
            $cgst = bcadd($cgst, bcmul((string) $poLine->cgst, $f, 2), 2);
            $sgst = bcadd($sgst, bcmul((string) $poLine->sgst, $f, 2), 2);
            $igst = bcadd($igst, bcmul((string) $poLine->igst, $f, 2), 2);
            $total = bcadd($total, bcmul((string) $poLine->line_total, $f, 2), 2);
        }

        if (bccomp($total, '0', 2) <= 0) {
            throw new InvalidArgumentException('GRN has no billable accepted quantity.');
        }

        $sumDr = bcadd(bcadd(bcadd($taxable, $cgst, 2), $sgst, 2), $igst, 2);
        if (bccomp($sumDr, $total, 2) !== 0) {
            throw new InvalidArgumentException('GRN amounts do not tie to PO tax totals.');
        }

        VendorPayable::query()->create([
            'goods_receipt_id' => $grn->id,
            'vendor_id' => $grn->vendor_id,
            'amount' => $total,
            'status' => 'open',
        ]);

        $this->journal->post(
            GoodsReceipt::class,
            $grn->id,
            'GRN '.$grn->grn_number,
            $userId,
            [
                ['code' => 'INV-ASSET', 'debit' => $taxable, 'credit' => '0.00'],
                ['code' => 'CGST-IN', 'debit' => $cgst, 'credit' => '0.00'],
                ['code' => 'SGST-IN', 'debit' => $sgst, 'credit' => '0.00'],
                ['code' => 'IGST-IN', 'debit' => $igst, 'credit' => '0.00'],
                ['code' => 'AP-TRADE', 'debit' => '0.00', 'credit' => $total],
            ]
        );
    }
}
