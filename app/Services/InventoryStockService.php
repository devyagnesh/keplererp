<?php

namespace App\Services;

use App\Models\BillOfMaterial;
use App\Models\GoodsReceipt;
use App\Models\GrnReturn;
use App\Models\InventoryBalance;
use App\Models\Item;
use App\Models\ProductionOrder;
use App\Models\ProductionStockReservation;
use App\Models\SalesOrder;
use App\Models\StockMovement;
use App\Models\StockReservation;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Throwable;

/**
 * Stock adjustments, transfers, GRN posting, reservations, dispatch, and production I/O.
 */
class InventoryStockService
{
    public function __construct(
        protected StockLedgerService $ledger,
        protected BatchSerialInventoryService $batchSerial
    ) {}

    /**
     * Apply a signed quantity change at one warehouse (adjustment).
     *
     * @param  array{notes?: string|null, batch_no?: string|null, serial_no?: string|null}  $meta
     *
     * @throws Throwable
     */
    public function adjust(int $warehouseId, int $itemId, string $signedDelta, ?int $userId, array $meta = []): void
    {
        DB::transaction(function () use ($warehouseId, $itemId, $signedDelta, $userId, $meta): void {
            $this->assertActiveWarehouseAndItem($warehouseId, $itemId);
            $item = Item::query()->findOrFail($itemId);
            $absQty = bccomp($signedDelta, '0', 4) >= 0 ? $signedDelta : bcmul($signedDelta, '-1', 4);
            if (bccomp($signedDelta, '0', 4) > 0) {
                $this->batchSerial->validateInboundLine($item, array_merge($meta, ['quantity' => $absQty]));
            } elseif (bccomp($signedDelta, '0', 4) < 0) {
                $this->batchSerial->validateOutboundLine($item, $warehouseId, array_merge($meta, ['quantity' => $absQty]));
            }
            $balance = $this->lockBalance($warehouseId, $itemId);
            $newQty = bcadd($balance->quantity, $signedDelta, 4);
            if (bccomp($newQty, '0', 4) < 0) {
                throw new InvalidArgumentException('Resulting quantity cannot be negative.');
            }
            $balance->update(['quantity' => $newQty]);
            StockMovement::query()->create([
                'movement_type' => 'adjustment',
                'warehouse_id' => $warehouseId,
                'to_warehouse_id' => null,
                'item_id' => $itemId,
                'quantity' => $signedDelta,
                'batch_no' => $meta['batch_no'] ?? null,
                'serial_no' => $meta['serial_no'] ?? null,
                'reference_type' => null,
                'reference_id' => null,
                'notes' => $meta['notes'] ?? null,
                'created_by' => $userId,
            ]);
            $ledgerMeta = [
                'batch_no' => $meta['batch_no'] ?? null,
                'serial_no' => $meta['serial_no'] ?? null,
            ];
            if (bccomp($signedDelta, '0', 4) > 0) {
                $this->ledger->recordIn($warehouseId, $itemId, 'ADJUSTMENT_IN', null, null, $signedDelta, $userId, $ledgerMeta);
            } elseif (bccomp($signedDelta, '0', 4) < 0) {
                $this->ledger->recordOut($warehouseId, $itemId, 'ADJUSTMENT_OUT', null, null, bcmul($signedDelta, '-1', 4), $userId, $ledgerMeta);
            }
        });
    }

    /**
     * Move quantity from one warehouse to another.
     *
     * @throws Throwable
     */
    /**
     * @param  array{batch_no?: string|null, serial_no?: string|null}  $meta
     */
    public function transfer(int $fromWarehouseId, int $toWarehouseId, int $itemId, string $qty, ?int $userId, ?string $notes = null, array $meta = []): void
    {
        if ($fromWarehouseId === $toWarehouseId) {
            throw new InvalidArgumentException('Source and destination warehouses must differ.');
        }
        DB::transaction(function () use ($fromWarehouseId, $toWarehouseId, $itemId, $qty, $userId, $notes, $meta): void {
            $this->assertActiveWarehouseAndItem($fromWarehouseId, $itemId);
            $this->assertActiveWarehouseAndItem($toWarehouseId, $itemId);
            if (bccomp($qty, '0', 4) <= 0) {
                throw new InvalidArgumentException('Transfer quantity must be positive.');
            }
            $item = Item::query()->findOrFail($itemId);
            $this->batchSerial->validateOutboundLine($item, $fromWarehouseId, array_merge($meta, ['quantity' => $qty]));
            $this->batchSerial->validateInboundLine($item, array_merge($meta, ['quantity' => $qty]));
            $ledgerMeta = [
                'batch_no' => $meta['batch_no'] ?? null,
                'serial_no' => $meta['serial_no'] ?? null,
            ];
            $neg = bcmul($qty, '-1', 4);
            $from = $this->lockBalance($fromWarehouseId, $itemId);
            $newFrom = bcadd($from->quantity, $neg, 4);
            if (bccomp($newFrom, '0', 4) < 0) {
                throw new InvalidArgumentException('Insufficient quantity at source warehouse.');
            }
            $from->update(['quantity' => $newFrom]);
            $to = $this->lockBalance($toWarehouseId, $itemId);
            $to->update(['quantity' => bcadd($to->quantity, $qty, 4)]);
            StockMovement::query()->create([
                'movement_type' => 'transfer_out',
                'warehouse_id' => $fromWarehouseId,
                'to_warehouse_id' => $toWarehouseId,
                'item_id' => $itemId,
                'quantity' => $neg,
                'batch_no' => $ledgerMeta['batch_no'],
                'serial_no' => $ledgerMeta['serial_no'],
                'reference_type' => null,
                'reference_id' => null,
                'notes' => $notes,
                'created_by' => $userId,
            ]);
            StockMovement::query()->create([
                'movement_type' => 'transfer_in',
                'warehouse_id' => $toWarehouseId,
                'to_warehouse_id' => $fromWarehouseId,
                'item_id' => $itemId,
                'quantity' => $qty,
                'batch_no' => $ledgerMeta['batch_no'],
                'serial_no' => $ledgerMeta['serial_no'],
                'reference_type' => null,
                'reference_id' => null,
                'notes' => $notes,
                'created_by' => $userId,
            ]);
            $this->ledger->recordOut($fromWarehouseId, $itemId, 'TRANSFER_OUT', null, null, $qty, $userId, $ledgerMeta);
            $this->ledger->recordIn($toWarehouseId, $itemId, 'TRANSFER_IN', null, null, $qty, $userId, $ledgerMeta);
        });
    }

    /**
     * Post GRN accepted quantities into stock.
     *
     * @param  list<array{item_id: int, quantity?: string, accepted_qty?: string, batch_no?: string|null, serial_no?: string|null, expiry_date?: string|null}>  $lines
     *
     * @throws Throwable
     */
    public function applyGoodsReceipt(GoodsReceipt $grn, array $lines, ?int $userId): void
    {
        DB::transaction(function () use ($grn, $lines, $userId): void {
            foreach ($lines as $line) {
                $itemId = (int) $line['item_id'];
                $qty = (string) ($line['accepted_qty'] ?? $line['quantity']);
                $meta = [
                    'batch_no' => $line['batch_no'] ?? null,
                    'serial_no' => $line['serial_no'] ?? null,
                    'expiry_date' => $line['expiry_date'] ?? null,
                ];
                $item = Item::query()->findOrFail($itemId);
                $this->batchSerial->validateInboundLine($item, array_merge($meta, ['quantity' => $qty]));
                $this->assertActiveWarehouseAndItem((int) $grn->warehouse_id, $itemId);
                if (bccomp($qty, '0', 4) <= 0) {
                    throw new InvalidArgumentException('GRN accepted quantity must be positive.');
                }
                $balance = $this->lockBalance((int) $grn->warehouse_id, $itemId);
                $balance->update(['quantity' => bcadd($balance->quantity, $qty, 4)]);
                StockMovement::query()->create([
                    'movement_type' => 'grn',
                    'warehouse_id' => $grn->warehouse_id,
                    'to_warehouse_id' => null,
                    'item_id' => $itemId,
                    'quantity' => $qty,
                    'batch_no' => $meta['batch_no'],
                    'serial_no' => $meta['serial_no'],
                    'reference_type' => GoodsReceipt::class,
                    'reference_id' => $grn->id,
                    'notes' => null,
                    'created_by' => $userId,
                ]);
                $this->ledger->recordIn(
                    (int) $grn->warehouse_id,
                    $itemId,
                    'GRN_IN',
                    GoodsReceipt::class,
                    $grn->id,
                    $qty,
                    $userId,
                    $meta
                );
            }
        });
    }

    /**
     * Reserve stock for each sales order line (confirmed orders).
     *
     * @throws Throwable
     */
    public function reserveForSalesOrder(SalesOrder $so, ?int $userId): void
    {
        DB::transaction(function () use ($so): void {
            $warehouseId = (int) $so->warehouse_id;
            if ($warehouseId <= 0) {
                throw new InvalidArgumentException('Sales order warehouse is required for reservations.');
            }
            $so->loadMissing('lines');
            foreach ($so->lines as $line) {
                $qty = (string) $line->quantity;
                $this->assertAvailable($warehouseId, (int) $line->item_id, $qty);
                StockReservation::query()->create([
                    'sales_order_id' => $so->id,
                    'item_id' => $line->item_id,
                    'warehouse_id' => $warehouseId,
                    'quantity' => $qty,
                    'status' => 'reserved',
                ]);
            }
        });
    }

    /**
     * Deduct stock for dispatch and consume reservations.
     *
     * @throws Throwable
     */
    public function applySalesDispatch(SalesOrder $so, ?int $userId): void
    {
        DB::transaction(function () use ($so, $userId): void {
            $warehouseId = (int) $so->warehouse_id;
            if ($warehouseId <= 0) {
                throw new InvalidArgumentException('Sales order warehouse is required for dispatch.');
            }
            $so->loadMissing('lines');
            foreach ($so->lines as $line) {
                $qty = (string) $line->quantity;
                $item = $line->item ?? Item::query()->findOrFail((int) $line->item_id);
                $meta = [
                    'batch_no' => $line->batch_no,
                    'serial_no' => $line->serial_no,
                ];
                $this->batchSerial->validateOutboundLine($item, $warehouseId, array_merge($meta, ['quantity' => $qty]));
                $this->assertActiveWarehouseAndItem($warehouseId, (int) $line->item_id);
                $balance = $this->lockBalance($warehouseId, (int) $line->item_id);
                $neg = bcmul($qty, '-1', 4);
                $newQty = bcadd($balance->quantity, $neg, 4);
                if (bccomp($newQty, '0', 4) < 0) {
                    throw new InvalidArgumentException('Insufficient stock to dispatch line item '.$line->item_id.'.');
                }
                $balance->update(['quantity' => $newQty]);
                StockMovement::query()->create([
                    'movement_type' => 'sales_dispatch',
                    'warehouse_id' => $warehouseId,
                    'to_warehouse_id' => null,
                    'item_id' => $line->item_id,
                    'quantity' => $neg,
                    'batch_no' => $meta['batch_no'],
                    'serial_no' => $meta['serial_no'],
                    'reference_type' => SalesOrder::class,
                    'reference_id' => $so->id,
                    'notes' => null,
                    'created_by' => $userId,
                ]);
                $this->ledger->recordOut(
                    $warehouseId,
                    (int) $line->item_id,
                    'SALES_OUT',
                    SalesOrder::class,
                    $so->id,
                    $qty,
                    $userId,
                    $meta
                );
            }
            StockReservation::query()
                ->where('sales_order_id', $so->id)
                ->where('status', 'reserved')
                ->update(['status' => 'consumed']);
        });
    }

    /**
     * Consume BOM components and receive finished goods for a completed work order.
     *
     * @throws Throwable
     */
    /**
     * Reverse stock for a posted GRN return (outbound from GRN warehouse).
     *
     * @throws Throwable
     */
    public function applyGrnReturn(GrnReturn $grnReturn, ?int $userId): void
    {
        $grnReturn->loadMissing(['lines', 'goodsReceipt']);
        $grn = $grnReturn->goodsReceipt;
        if ($grn === null) {
            throw new InvalidArgumentException('Goods receipt is required for a return.');
        }

        DB::transaction(function () use ($grnReturn, $grn, $userId): void {
            foreach ($grnReturn->lines as $line) {
                $qty = (string) $line->quantity;
                if (bccomp($qty, '0', 4) <= 0) {
                    continue;
                }
                $neg = bcmul($qty, '-1', 4);
                $this->adjust((int) $grn->warehouse_id, (int) $line->item_id, $neg, $userId, [
                    'notes' => 'GRN return '.$grnReturn->return_number,
                    'batch_no' => $line->batch_no,
                ]);
            }
        });
    }

    public function applyProductionCompletion(ProductionOrder $order, BillOfMaterial $bom, string $outputQty, ?int $userId): void
    {
        DB::transaction(function () use ($order, $bom, $outputQty, $userId): void {
            $warehouseId = (int) $order->warehouse_id;
            if ($warehouseId <= 0) {
                throw new InvalidArgumentException('Production order warehouse is required.');
            }
            $order->loadMissing('materials');
            $materialQtyByItem = [];
            foreach ($order->materials as $material) {
                $qty = $material->actual_qty ?? $material->planned_qty;
                if ($qty !== null && bccomp((string) $qty, '0', 4) > 0) {
                    $materialQtyByItem[(int) $material->item_id] = (string) $qty;
                }
            }
            $bom->loadMissing('lines');
            foreach ($bom->lines as $bomLine) {
                $componentId = (int) $bomLine->component_item_id;
                if (isset($materialQtyByItem[$componentId])) {
                    $need = $materialQtyByItem[$componentId];
                } else {
                    $need = bcmul((string) $bomLine->quantity_per, $outputQty, 6);
                    $need = bcadd($need, '0', 4);
                }
                if (bccomp($need, '0', 4) <= 0) {
                    continue;
                }
                $this->assertActiveWarehouseAndItem($warehouseId, (int) $bomLine->component_item_id);
                $balance = $this->lockBalance($warehouseId, (int) $bomLine->component_item_id);
                $neg = bcmul($need, '-1', 4);
                $newQty = bcadd($balance->quantity, $neg, 4);
                if (bccomp($newQty, '0', 4) < 0) {
                    throw new InvalidArgumentException('Insufficient raw material for component item '.$bomLine->component_item_id.'.');
                }
                $balance->update(['quantity' => $newQty]);
                StockMovement::query()->create([
                    'movement_type' => 'production_consume',
                    'warehouse_id' => $warehouseId,
                    'to_warehouse_id' => null,
                    'item_id' => $bomLine->component_item_id,
                    'quantity' => $neg,
                    'reference_type' => ProductionOrder::class,
                    'reference_id' => $order->id,
                    'notes' => null,
                    'created_by' => $userId,
                ]);
                $this->ledger->recordOut(
                    $warehouseId,
                    (int) $bomLine->component_item_id,
                    'PRODUCTION_CONSUMPTION',
                    ProductionOrder::class,
                    $order->id,
                    $need,
                    $userId,
                    []
                );
            }
            $fgId = (int) $order->item_id;
            $this->assertActiveWarehouseAndItem($warehouseId, $fgId);
            $fgBalance = $this->lockBalance($warehouseId, $fgId);
            $fgBalance->update(['quantity' => bcadd($fgBalance->quantity, $outputQty, 4)]);
            StockMovement::query()->create([
                'movement_type' => 'production_output',
                'warehouse_id' => $warehouseId,
                'to_warehouse_id' => null,
                'item_id' => $fgId,
                'quantity' => $outputQty,
                'reference_type' => ProductionOrder::class,
                'reference_id' => $order->id,
                'notes' => null,
                'created_by' => $userId,
            ]);
            $this->ledger->recordIn(
                $warehouseId,
                $fgId,
                'PRODUCTION_OUTPUT',
                ProductionOrder::class,
                $order->id,
                $outputQty,
                $userId,
                []
            );
            ProductionStockReservation::query()
                ->where('production_order_id', $order->id)
                ->where('status', 'reserved')
                ->update(['status' => 'consumed']);
            $order->update(['actual_end' => now()]);
        });
    }

    public function assertAvailableForProduction(int $warehouseId, int $itemId, string $qty): void
    {
        $this->assertAvailable($warehouseId, $itemId, $qty);
    }

    protected function assertAvailable(int $warehouseId, int $itemId, string $qty): void
    {
        $this->assertActiveWarehouseAndItem($warehouseId, $itemId);
        $balance = InventoryBalance::query()
            ->where('warehouse_id', $warehouseId)
            ->where('item_id', $itemId)
            ->first();
        $bal = $balance?->quantity ?? '0.0000';
        $reserved = (string) StockReservation::query()
            ->where('warehouse_id', $warehouseId)
            ->where('item_id', $itemId)
            ->where('status', 'reserved')
            ->sum('quantity');
        $reserved = bcadd($reserved, (string) ProductionStockReservation::query()
            ->where('warehouse_id', $warehouseId)
            ->where('item_id', $itemId)
            ->where('status', 'reserved')
            ->sum('quantity'), 4);
        $avail = bcsub($bal, $reserved, 4);
        if (bccomp($avail, $qty, 4) < 0) {
            throw new InvalidArgumentException('Insufficient available quantity for item '.$itemId.' at warehouse.');
        }
    }

    protected function assertActiveWarehouseAndItem(int $warehouseId, int $itemId): void
    {
        if (! Warehouse::query()->whereKey($warehouseId)->where('is_active', true)->exists()) {
            throw new InvalidArgumentException('Invalid or inactive warehouse.');
        }
        if (! Item::query()->whereKey($itemId)->where('is_active', true)->exists()) {
            throw new InvalidArgumentException('Invalid or inactive item.');
        }
    }

    /**
     * @return InventoryBalance
     */
    protected function lockBalance(int $warehouseId, int $itemId)
    {
        InventoryBalance::query()->firstOrCreate(
            [
                'warehouse_id' => $warehouseId,
                'item_id' => $itemId,
            ],
            ['quantity' => '0.0000']
        );

        /** @var InventoryBalance $row */
        $row = InventoryBalance::query()
            ->where('warehouse_id', $warehouseId)
            ->where('item_id', $itemId)
            ->lockForUpdate()
            ->firstOrFail();

        return $row;
    }
}
