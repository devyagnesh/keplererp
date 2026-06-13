<?php

namespace App\Services;

use App\Models\User;
use App\Models\WarehouseTransfer;
use App\Models\WarehouseTransferLine;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Throwable;

/**
 * Inter-warehouse transfer workflow: draft → approve → dispatch → receive (SRS UC 22.7).
 */
class WarehouseTransferService
{
    public function __construct(
        protected DocumentNumberService $documentNumbers,
        protected InventoryStockService $inventory
    ) {}

    /**
     * @param  list<array{item_id: int, qty_requested: string, batch_no?: string|null, serial_no?: string|null}>  $lines
     *
     * @throws Throwable
     */
    public function createDraft(
        int $fromWarehouseId,
        int $toWarehouseId,
        ?string $reason,
        array $lines,
        User $user
    ): WarehouseTransfer {
        if ($fromWarehouseId === $toWarehouseId) {
            throw new InvalidArgumentException('Source and destination warehouses must differ.');
        }
        if ($lines === []) {
            throw new InvalidArgumentException('At least one line item is required.');
        }

        return DB::transaction(function () use ($fromWarehouseId, $toWarehouseId, $reason, $lines, $user): WarehouseTransfer {
            $transfer = WarehouseTransfer::query()->create([
                'transfer_number' => $this->documentNumbers->next('warehouse_transfers', 'WT-'),
                'from_warehouse_id' => $fromWarehouseId,
                'to_warehouse_id' => $toWarehouseId,
                'status' => WarehouseTransfer::STATUS_DRAFT,
                'reason' => $reason,
                'created_by' => $user->id,
            ]);

            foreach ($lines as $line) {
                if (bccomp((string) $line['qty_requested'], '0', 4) <= 0) {
                    throw new InvalidArgumentException('Requested quantity must be positive.');
                }
                WarehouseTransferLine::query()->create([
                    'warehouse_transfer_id' => $transfer->id,
                    'item_id' => (int) $line['item_id'],
                    'qty_requested' => (string) $line['qty_requested'],
                    'batch_no' => $line['batch_no'] ?? null,
                    'serial_no' => $line['serial_no'] ?? null,
                ]);
            }

            return $transfer->load(['lines.item', 'fromWarehouse', 'toWarehouse']);
        });
    }

    /**
     * @throws Throwable
     */
    public function approve(WarehouseTransfer $transfer, User $user): WarehouseTransfer
    {
        $this->assertStatus($transfer, WarehouseTransfer::STATUS_DRAFT);

        $transfer->update([
            'status' => WarehouseTransfer::STATUS_APPROVED,
            'approved_by' => $user->id,
            'approved_at' => now(),
        ]);

        return $transfer->fresh();
    }

    /**
     * @param  list<array{id: int, qty_dispatched: string}>|null  $lineQtys
     *
     * @throws Throwable
     */
    public function dispatch(WarehouseTransfer $transfer, User $user, ?string $vehicleNo = null, ?string $lrNumber = null, ?array $lineQtys = null): WarehouseTransfer
    {
        $this->assertStatus($transfer, WarehouseTransfer::STATUS_APPROVED);

        return DB::transaction(function () use ($transfer, $user, $vehicleNo, $lrNumber, $lineQtys): WarehouseTransfer {
            $transfer->load('lines');
            $qtyMap = [];
            if ($lineQtys !== null) {
                foreach ($lineQtys as $row) {
                    $qtyMap[(int) $row['id']] = (string) $row['qty_dispatched'];
                }
            }

            foreach ($transfer->lines as $line) {
                $qty = $qtyMap[$line->id] ?? (string) $line->qty_requested;
                if (bccomp($qty, '0', 4) <= 0) {
                    throw new InvalidArgumentException('Dispatch quantity must be positive.');
                }
                if (bccomp($qty, (string) $line->qty_requested, 4) > 0) {
                    throw new InvalidArgumentException('Dispatch quantity cannot exceed requested quantity.');
                }

                $this->inventory->adjust(
                    (int) $transfer->from_warehouse_id,
                    (int) $line->item_id,
                    bcmul($qty, '-1', 4),
                    $user->id,
                    [
                        'notes' => 'Transfer '.$transfer->transfer_number.' dispatch (in transit)',
                        'batch_no' => $line->batch_no,
                        'serial_no' => $line->serial_no,
                    ]
                );

                $line->update(['qty_dispatched' => $qty]);
            }

            $transfer->update([
                'status' => WarehouseTransfer::STATUS_IN_TRANSIT,
                'vehicle_no' => $vehicleNo,
                'lr_number' => $lrNumber,
                'dispatched_at' => now(),
            ]);

            return $transfer->fresh(['lines.item', 'fromWarehouse', 'toWarehouse']);
        });
    }

    /**
     * @param  list<array{id: int, qty_received: string, variance_reason?: string|null}>  $lineReceipts
     *
     * @throws Throwable
     */
    public function receive(WarehouseTransfer $transfer, User $user, array $lineReceipts): WarehouseTransfer
    {
        $this->assertStatus($transfer, WarehouseTransfer::STATUS_IN_TRANSIT);

        return DB::transaction(function () use ($transfer, $user, $lineReceipts): WarehouseTransfer {
            $transfer->load('lines');
            $receiptMap = [];
            foreach ($lineReceipts as $row) {
                $receiptMap[(int) $row['id']] = $row;
            }

            foreach ($transfer->lines as $line) {
                $receipt = $receiptMap[$line->id] ?? null;
                if ($receipt === null) {
                    throw new InvalidArgumentException('Receipt quantity required for every line.');
                }
                $received = (string) $receipt['qty_received'];
                $dispatched = (string) ($line->qty_dispatched ?? $line->qty_requested);
                if (bccomp($received, '0', 4) < 0) {
                    throw new InvalidArgumentException('Received quantity cannot be negative.');
                }
                if (bccomp($received, $dispatched, 4) > 0) {
                    throw new InvalidArgumentException('Received quantity cannot exceed dispatched quantity.');
                }

                $variance = bcsub($dispatched, $received, 4);
                $this->inventory->adjust(
                    (int) $transfer->to_warehouse_id,
                    (int) $line->item_id,
                    $received,
                    $user->id,
                    [
                        'notes' => 'Transfer '.$transfer->transfer_number.' received',
                        'batch_no' => $line->batch_no,
                        'serial_no' => $line->serial_no,
                    ]
                );

                if (bccomp($variance, '0', 4) !== 0) {
                    $reason = trim((string) ($receipt['variance_reason'] ?? ''));
                    if ($reason === '') {
                        throw new InvalidArgumentException('Variance reason is required when received qty differs from dispatched.');
                    }
                    $line->update([
                        'qty_received' => $received,
                        'variance_reason' => $reason,
                    ]);
                } else {
                    $line->update(['qty_received' => $received]);
                }
            }

            $transfer->update([
                'status' => WarehouseTransfer::STATUS_RECEIVED,
                'received_at' => now(),
                'received_by' => $user->id,
            ]);

            return $transfer->fresh(['lines.item', 'fromWarehouse', 'toWarehouse']);
        });
    }

    /**
     * @throws Throwable
     */
    public function cancel(WarehouseTransfer $transfer): WarehouseTransfer
    {
        if (! in_array($transfer->status, [WarehouseTransfer::STATUS_DRAFT, WarehouseTransfer::STATUS_APPROVED], true)) {
            throw new InvalidArgumentException('Only draft or approved transfers can be cancelled.');
        }

        $transfer->update(['status' => WarehouseTransfer::STATUS_CANCELLED]);

        return $transfer->fresh();
    }

    protected function assertStatus(WarehouseTransfer $transfer, string $expected): void
    {
        if ($transfer->status !== $expected) {
            throw new InvalidArgumentException('Transfer is not in the expected status: '.$expected.'.');
        }
    }
}
