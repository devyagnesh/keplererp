<?php

namespace App\Services;

use App\Models\StockLedger;
use InvalidArgumentException;

/**
 * Append-only stock ledger rows with running balance per warehouse + item.
 */
class StockLedgerService
{
    /**
     * @param  array{batch_no?: string|null, serial_no?: string|null, expiry_date?: string|null, unit_cost?: string|null}  $meta
     *
     */
    public function recordIn(
        int $warehouseId,
        int $itemId,
        string $transactionType,
        ?string $referenceType,
        ?int $referenceId,
        string $qtyIn,
        ?int $userId,
        array $meta = []
    ): void {
        if (bccomp($qtyIn, '0', 4) <= 0) {
            throw new InvalidArgumentException('qty_in must be positive.');
        }
        $this->append($warehouseId, $itemId, $transactionType, $referenceType, $referenceId, $qtyIn, null, $userId, $meta);
    }

    /**
     * @param  array{batch_no?: string|null, serial_no?: string|null, expiry_date?: string|null, unit_cost?: string|null}  $meta
     *
     */
    public function recordOut(
        int $warehouseId,
        int $itemId,
        string $transactionType,
        ?string $referenceType,
        ?int $referenceId,
        string $qtyOut,
        ?int $userId,
        array $meta = []
    ): void {
        if (bccomp($qtyOut, '0', 4) <= 0) {
            throw new InvalidArgumentException('qty_out must be positive.');
        }
        $this->append($warehouseId, $itemId, $transactionType, $referenceType, $referenceId, null, $qtyOut, $userId, $meta);
    }

    /**
     * @param  array{batch_no?: string|null, serial_no?: string|null, expiry_date?: string|null, unit_cost?: string|null}  $meta
     *
     */
    protected function append(
        int $warehouseId,
        int $itemId,
        string $transactionType,
        ?string $referenceType,
        ?int $referenceId,
        ?string $qtyIn,
        ?string $qtyOut,
        ?int $userId,
        array $meta
    ): void {
        $last = StockLedger::query()
            ->where('warehouse_id', $warehouseId)
            ->where('item_id', $itemId)
            ->orderByDesc('id')
            ->lockForUpdate()
            ->first();

        $prev = $last?->balance_qty ?? '0.0000';
        $deltaIn = $qtyIn ?? '0.0000';
        $deltaOut = $qtyOut ?? '0.0000';
        $newBal = bcadd(bcsub($prev, $deltaOut, 4), $deltaIn, 4);
        if (bccomp($newBal, '0', 4) < 0) {
            throw new InvalidArgumentException('Stock ledger balance cannot be negative.');
        }

        StockLedger::query()->create([
            'item_id' => $itemId,
            'warehouse_id' => $warehouseId,
            'batch_no' => $meta['batch_no'] ?? null,
            'serial_no' => $meta['serial_no'] ?? null,
            'expiry_date' => $meta['expiry_date'] ?? null,
            'transaction_type' => $transactionType,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'qty_in' => $qtyIn,
            'qty_out' => $qtyOut,
            'balance_qty' => $newBal,
            'unit_cost' => $meta['unit_cost'] ?? null,
            'created_by' => $userId,
            'created_at' => now(),
        ]);
    }
}
