<?php

namespace App\Services;

use App\Models\BillOfMaterial;
use App\Models\ProductionOrder;
use App\Models\ProductionOrderMaterial;
use App\Models\ProductionStockReservation;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Material planning and stock reservation when a work order starts (SRS production).
 */
class ProductionReleaseService
{
    public function __construct(protected InventoryStockService $stockService) {}

    public function releaseMaterials(ProductionOrder $order): void
    {
        if ($order->warehouse_id === null) {
            throw new InvalidArgumentException('Warehouse is required before releasing materials.');
        }

        if (ProductionOrderMaterial::query()->where('production_order_id', $order->id)->exists()) {
            return;
        }

        $bom = $order->bom_id !== null
            ? BillOfMaterial::query()->with('lines')->findOrFail((int) $order->bom_id)
            : BillOfMaterial::activeForItem((int) $order->item_id);

        if ($bom === null) {
            throw new InvalidArgumentException('No active BOM found for this work order.');
        }

        DB::transaction(function () use ($order, $bom): void {
            $bom->loadMissing('lines');
            foreach ($bom->lines as $bomLine) {
                $planned = bcmul((string) $bomLine->quantity_per, (string) $order->qty_planned, 4);
                if (bccomp($planned, '0', 4) <= 0) {
                    continue;
                }
                $this->stockService->assertAvailableForProduction(
                    (int) $order->warehouse_id,
                    (int) $bomLine->component_item_id,
                    $planned
                );
                ProductionOrderMaterial::query()->create([
                    'production_order_id' => $order->id,
                    'item_id' => $bomLine->component_item_id,
                    'planned_qty' => $planned,
                ]);
                ProductionStockReservation::query()->create([
                    'production_order_id' => $order->id,
                    'item_id' => $bomLine->component_item_id,
                    'warehouse_id' => $order->warehouse_id,
                    'quantity' => $planned,
                    'status' => 'reserved',
                ]);
            }
            $order->update(['actual_start' => now()]);
        });
    }
}
