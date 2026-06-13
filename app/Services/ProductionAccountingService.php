<?php

namespace App\Services;

use App\Models\BillOfMaterial;
use App\Models\ProductionOrder;
use App\Models\ProductionOrderMaterial;
use App\Models\PurchaseOrderLine;

/**
 * Production completion GL entries (SRS UC 22.3 step 5).
 */
class ProductionAccountingService
{
    public function __construct(
        protected AccountingJournalService $journal
    ) {}

    public function postCompletionJournal(ProductionOrder $order, BillOfMaterial $bom, string $outputQty, ?int $userId): void
    {
        $order->loadMissing(['materials.item']);
        $bom->loadMissing('lines');

        $rmValue = '0.00';
        foreach ($order->materials as $material) {
            if (! $material instanceof ProductionOrderMaterial) {
                continue;
            }
            $qty = $material->actual_qty ?? $material->planned_qty;
            if ($qty === null || bccomp((string) $qty, '0', 4) <= 0) {
                continue;
            }
            $unitCost = $this->estimateUnitCost((int) $material->item_id);
            $rmValue = bcadd($rmValue, bcmul((string) $qty, $unitCost, 2), 2);
        }

        if (bccomp($rmValue, '0', 2) <= 0) {
            $rmValue = bcmul($outputQty, '1.00', 2);
        }

        $fgValue = $rmValue;

        $this->journal->post(
            ProductionOrder::class,
            $order->id,
            'Production complete '.$order->wo_number,
            $userId,
            [
                ['code' => 'WIP-PROD', 'debit' => $rmValue, 'credit' => '0.00'],
                ['code' => 'INV-ASSET', 'debit' => '0.00', 'credit' => $rmValue],
                ['code' => 'INV-ASSET', 'debit' => $fgValue, 'credit' => '0.00'],
                ['code' => 'WIP-PROD', 'debit' => '0.00', 'credit' => $fgValue],
            ]
        );
    }

    protected function estimateUnitCost(int $itemId): string
    {
        $line = PurchaseOrderLine::query()
            ->where('item_id', $itemId)
            ->orderByDesc('id')
            ->first();

        if ($line !== null && bccomp((string) $line->unit_price, '0', 2) > 0) {
            return (string) $line->unit_price;
        }

        return '1.00';
    }
}
