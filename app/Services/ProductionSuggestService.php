<?php

namespace App\Services;

use App\Models\BillOfMaterial;
use App\Models\InventoryBalance;
use App\Models\ProductionOrder;
use App\Models\SalesOrder;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Suggests / creates work orders from sales orders when FG stock is low (SRS production link).
 */
class ProductionSuggestService
{
    public function __construct(protected DocumentNumberService $documentNumbers) {}

    /**
     * @return list<ProductionOrder>
     */
    public function createFromSalesOrder(SalesOrder $salesOrder, User $user): array
    {
        if ($salesOrder->warehouse_id === null) {
            throw new InvalidArgumentException('Sales order must have a warehouse before creating work orders.');
        }

        $salesOrder->loadMissing('lines.item');
        $created = [];

        DB::transaction(function () use ($salesOrder, $user, &$created): void {
            foreach ($salesOrder->lines as $line) {
                $item = $line->item;
                if ($item === null || $item->item_type !== 'FINISHED_GOOD') {
                    continue;
                }

                $bom = BillOfMaterial::activeForItem((int) $item->id);
                if ($bom === null) {
                    continue;
                }

                $onHand = (string) (InventoryBalance::query()
                    ->where('warehouse_id', $salesOrder->warehouse_id)
                    ->where('item_id', $item->id)
                    ->value('quantity') ?? '0');

                $need = bcsub((string) $line->quantity, $onHand, 4);
                if (bccomp($need, '0', 4) <= 0) {
                    continue;
                }

                $batchYield = bcadd((string) ($bom->batch_yield_qty ?? '1'), '0', 4);
                if (bccomp($batchYield, '0', 4) <= 0) {
                    $batchYield = '1.0000';
                }
                $batches = bcdiv($need, $batchYield, 0);
                if (bccomp(bcmul($batches, $batchYield, 4), $need, 4) < 0) {
                    $batches = bcadd($batches, '1', 0);
                }
                $plannedQty = bcmul($batches, $batchYield, 4);

                $wo = ProductionOrder::query()->create([
                    'wo_number' => $this->documentNumbers->next('production_orders', 'WO-'),
                    'item_id' => $item->id,
                    'bom_id' => $bom->id,
                    'warehouse_id' => $salesOrder->warehouse_id,
                    'sales_order_id' => $salesOrder->id,
                    'qty_planned' => $plannedQty,
                    'status' => 'planned',
                    'created_by' => $user->id,
                    'notes' => 'Auto-suggested from SO '.$salesOrder->order_number,
                ]);
                $created[] = $wo;
            }
        });

        if ($created === []) {
            throw new InvalidArgumentException('No work orders needed: stock is sufficient or no BOM for finished goods.');
        }

        return $created;
    }
}
