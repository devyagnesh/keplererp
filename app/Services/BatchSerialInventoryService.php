<?php

namespace App\Services;

use App\Models\Item;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Batch and serial tracking helpers based on stock_ledger movements.
 */
class BatchSerialInventoryService
{
    /**
     * @return array{is_batch_tracked: bool, is_serial_tracked: bool}
     */
    public function trackingForItem(int $itemId): array
    {
        $item = Item::query()->find($itemId);
        if ($item === null) {
            return ['is_batch_tracked' => false, 'is_serial_tracked' => false];
        }

        return [
            'is_batch_tracked' => (bool) $item->is_batch_tracked,
            'is_serial_tracked' => (bool) $item->is_serial_tracked,
        ];
    }

    /**
     * @return list<array{batch_no: string, on_hand: string, expiry_date: string|null}>
     */
    public function availableBatches(int $warehouseId, int $itemId): array
    {
        $rows = DB::table('stock_ledger')
            ->select('batch_no')
            ->selectRaw('SUM(COALESCE(qty_in, 0)) - SUM(COALESCE(qty_out, 0)) as on_hand')
            ->selectRaw('MIN(expiry_date) as expiry_date')
            ->where('warehouse_id', $warehouseId)
            ->where('item_id', $itemId)
            ->whereNotNull('batch_no')
            ->where('batch_no', '!=', '')
            ->groupBy('batch_no')
            ->havingRaw('on_hand > 0')
            ->orderByRaw('MIN(expiry_date) IS NULL, MIN(expiry_date) ASC')
            ->orderBy('batch_no')
            ->get();

        return $rows->map(fn ($row) => [
            'batch_no' => (string) $row->batch_no,
            'on_hand' => bcadd((string) $row->on_hand, '0', 4),
            'expiry_date' => $row->expiry_date !== null ? (string) $row->expiry_date : null,
        ])->values()->all();
    }

    /**
     * @return list<array{serial_no: string}>
     */
    public function availableSerials(int $warehouseId, int $itemId): array
    {
        $rows = DB::table('stock_ledger')
            ->select('serial_no')
            ->selectRaw('SUM(COALESCE(qty_in, 0)) - SUM(COALESCE(qty_out, 0)) as on_hand')
            ->where('warehouse_id', $warehouseId)
            ->where('item_id', $itemId)
            ->whereNotNull('serial_no')
            ->where('serial_no', '!=', '')
            ->groupBy('serial_no')
            ->havingRaw('on_hand > 0')
            ->orderBy('serial_no')
            ->get();

        return $rows->map(fn ($row) => [
            'serial_no' => (string) $row->serial_no,
        ])->values()->all();
    }

    /**
     * Validate inbound line (GRN, positive adjustment).
     *
     * @param  array{batch_no?: string|null, serial_no?: string|null, quantity?: string}  $line
     */
    public function validateInboundLine(Item $item, array $line): void
    {
        $batch = trim((string) ($line['batch_no'] ?? ''));
        $serial = trim((string) ($line['serial_no'] ?? ''));
        $qty = (string) ($line['quantity'] ?? $line['accepted_qty'] ?? '0');

        if ($item->is_serial_tracked) {
            if ($serial === '') {
                throw new InvalidArgumentException("Serial number is required for {$item->sku}.");
            }
            if (bccomp($qty, '1', 4) !== 0) {
                throw new InvalidArgumentException("Serial-tracked item {$item->sku} must have quantity 1 per serial.");
            }
            if ($batch !== '') {
                throw new InvalidArgumentException("Use serial number only for serial-tracked item {$item->sku}.");
            }

            return;
        }

        if ($item->is_batch_tracked && $batch === '') {
            throw new InvalidArgumentException("Batch number is required for batch-tracked item {$item->sku}.");
        }

        if (! $item->is_batch_tracked && ! $item->is_serial_tracked) {
            if ($batch !== '' || $serial !== '') {
                throw new InvalidArgumentException("Item {$item->sku} is not batch/serial tracked.");
            }
        }
    }

    /**
     * Validate outbound line (dispatch, transfer out, negative adjust, GRN return).
     *
     * @param  array{batch_no?: string|null, serial_no?: string|null, quantity: string}  $line
     */
    public function validateOutboundLine(Item $item, int $warehouseId, array $line): void
    {
        $batch = trim((string) ($line['batch_no'] ?? ''));
        $serial = trim((string) ($line['serial_no'] ?? ''));
        $qty = (string) $line['quantity'];

        if ($item->is_serial_tracked) {
            if ($serial === '') {
                throw new InvalidArgumentException("Serial number is required for {$item->sku}.");
            }
            if (bccomp($qty, '1', 4) !== 0) {
                throw new InvalidArgumentException("Serial-tracked item {$item->sku} must have quantity 1.");
            }
            $this->assertSerialOnHand($warehouseId, $item->id, $serial);

            return;
        }

        if ($item->is_batch_tracked) {
            if ($batch === '') {
                throw new InvalidArgumentException("Batch number is required for batch-tracked item {$item->sku}.");
            }
            $this->assertBatchOnHand($warehouseId, $item->id, $batch, $qty);
        }
    }

    public function assertBatchOnHand(int $warehouseId, int $itemId, string $batchNo, string $qty): void
    {
        $available = collect($this->availableBatches($warehouseId, $itemId))
            ->firstWhere('batch_no', $batchNo);
        if ($available === null || bccomp((string) $available['on_hand'], $qty, 4) < 0) {
            throw new InvalidArgumentException("Insufficient batch {$batchNo} quantity for item {$itemId}.");
        }
    }

    public function assertSerialOnHand(int $warehouseId, int $itemId, string $serialNo): void
    {
        $found = collect($this->availableSerials($warehouseId, $itemId))
            ->contains(fn (array $row): bool => $row['serial_no'] === $serialNo);
        if (! $found) {
            throw new InvalidArgumentException("Serial {$serialNo} is not available in stock for item {$itemId}.");
        }
    }

    /**
     * @param  list<array{item_id: int, batch_no?: string|null}>  $lines
     * @return list<array{batch_no: string, on_hand: string}>
     */
    public function batchesFromGrnLines(int $warehouseId, array $lines): array
    {
        $out = [];
        foreach ($lines as $line) {
            $batch = trim((string) ($line['batch_no'] ?? ''));
            if ($batch === '') {
                continue;
            }
            $itemId = (int) $line['item_id'];
            if (! isset($out[$itemId][$batch])) {
                $onHand = collect($this->availableBatches($warehouseId, $itemId))
                    ->firstWhere('batch_no', $batch);
                $out[$itemId][$batch] = $onHand['on_hand'] ?? '0';
            }
        }

        return $out;
    }
}
