<?php

namespace App\Services\Sales;

use App\Models\InventoryBalance;
use App\Models\SalesOrder;
use App\Models\SalesOrderLine;
use InvalidArgumentException;

/**
 * Pick list data and barcode validation (SRS UC 22.2).
 */
class SalesPickListService
{
    /**
     * @return array{order: SalesOrder, lines: list<array<string, mixed>>}
     */
    public function buildPickList(SalesOrder $order): array
    {
        if (! in_array($order->status, ['processing', 'confirmed'], true)) {
            throw new InvalidArgumentException('Pick list is only available for confirmed or processing orders.');
        }

        $order->loadMissing(['lines.item', 'customer', 'warehouse']);
        $warehouseId = (int) $order->warehouse_id;

        $lines = $order->lines->map(function (SalesOrderLine $line) use ($warehouseId) {
            $item = $line->item;
            $onHand = '0';
            if ($warehouseId > 0 && $item !== null) {
                $bal = InventoryBalance::query()
                    ->where('warehouse_id', $warehouseId)
                    ->where('item_id', $item->id)
                    ->value('quantity');
                $onHand = (string) ($bal ?? '0');
            }

            return [
                'line_id' => $line->id,
                'item_id' => $line->item_id,
                'sku' => $item?->sku ?? '',
                'item_label' => $item?->display_label ?? '—',
                'quantity' => (string) $line->quantity,
                'on_hand' => $onHand,
                'batch_no' => $line->batch_no,
            ];
        })->values()->all();

        return ['order' => $order, 'lines' => $lines];
    }

    /**
     * Validate scanned barcode against order lines.
     *
     * @param  list<string>  $scannedCodes
     * @return array{matched: int, total: int, unmatched: list<string>}
     */
    public function validateScans(SalesOrder $order, array $scannedCodes): array
    {
        $order->loadMissing('lines.item');
        $expected = $order->lines->map(fn (SalesOrderLine $l) => strtoupper((string) ($l->item?->sku ?? '')))->filter()->values();
        $scanned = collect($scannedCodes)->map(fn (string $c) => strtoupper(trim($c)))->filter()->values();

        $matched = 0;
        $unmatched = [];
        $remaining = $expected->countBy()->all();

        foreach ($scanned as $code) {
            if (($remaining[$code] ?? 0) > 0) {
                $remaining[$code]--;
                $matched++;
            } else {
                $unmatched[] = $code;
            }
        }

        return [
            'matched' => $matched,
            'total' => $expected->count(),
            'unmatched' => $unmatched,
        ];
    }

    public function confirmPick(SalesOrder $order, ?string $packagingNotes = null): SalesOrder
    {
        if ($order->status !== 'processing') {
            throw new InvalidArgumentException('Only processing orders can confirm pick.');
        }

        $order->update([
            'pick_confirmed_at' => now(),
            'packaging_notes' => $packagingNotes,
        ]);

        return $order->fresh();
    }
}
