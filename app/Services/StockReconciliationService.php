<?php

namespace App\Services;

use App\Models\InventoryBalance;
use App\Models\PurchaseOrderLine;
use App\Models\StockReconciliation;
use App\Models\StockReconciliationLine;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Throwable;

/**
 * Physical stock count vs system reconciliation (SRS UC 22.7).
 */
class StockReconciliationService
{
    public function __construct(
        protected DocumentNumberService $documentNumbers,
        protected InventoryStockService $inventory,
        protected AccountingJournalService $journal
    ) {}

    /**
     * @throws Throwable
     */
    public function createDraft(int $warehouseId, int $year, int $month, User $user): StockReconciliation
    {
        return DB::transaction(function () use ($warehouseId, $year, $month, $user): StockReconciliation {
            $existing = StockReconciliation::query()
                ->where('warehouse_id', $warehouseId)
                ->where('period_year', $year)
                ->where('period_month', $month)
                ->where('status', StockReconciliation::STATUS_DRAFT)
                ->exists();

            if ($existing) {
                throw new InvalidArgumentException('A draft reconciliation already exists for this warehouse and period.');
            }

            $recon = StockReconciliation::query()->create([
                'reconciliation_number' => $this->documentNumbers->next('stock_reconciliations', 'SR-'),
                'warehouse_id' => $warehouseId,
                'period_year' => $year,
                'period_month' => $month,
                'status' => StockReconciliation::STATUS_DRAFT,
                'created_by' => $user->id,
            ]);

            $balances = InventoryBalance::query()
                ->where('warehouse_id', $warehouseId)
                ->where('quantity', '>', 0)
                ->get(['item_id', 'quantity']);

            foreach ($balances as $balance) {
                StockReconciliationLine::query()->create([
                    'stock_reconciliation_id' => $recon->id,
                    'item_id' => $balance->item_id,
                    'system_qty' => (string) $balance->quantity,
                    'physical_qty' => (string) $balance->quantity,
                    'variance_qty' => '0',
                ]);
            }

            return $recon->load(['lines.item', 'warehouse']);
        });
    }

    /**
     * @param  list<array{id: int, physical_qty: string, reason?: string|null}>  $lines
     *
     * @throws Throwable
     */
    public function updateCounts(StockReconciliation $recon, array $lines): StockReconciliation
    {
        if ($recon->status !== StockReconciliation::STATUS_DRAFT) {
            throw new InvalidArgumentException('Only draft reconciliations can be updated.');
        }

        return DB::transaction(function () use ($recon, $lines): StockReconciliation {
            foreach ($lines as $row) {
                $line = StockReconciliationLine::query()
                    ->where('stock_reconciliation_id', $recon->id)
                    ->where('id', (int) $row['id'])
                    ->firstOrFail();

                $physical = (string) $row['physical_qty'];
                $variance = bcsub($physical, (string) $line->system_qty, 4);
                $line->update([
                    'physical_qty' => $physical,
                    'variance_qty' => $variance,
                    'reason' => $row['reason'] ?? null,
                ]);
            }

            return $recon->fresh(['lines.item', 'warehouse']);
        });
    }

    /**
     * Post variances as stock adjustments.
     *
     * @throws Throwable
     */
    public function post(StockReconciliation $recon, User $user): StockReconciliation
    {
        if ($recon->status !== StockReconciliation::STATUS_DRAFT) {
            throw new InvalidArgumentException('Only draft reconciliations can be posted.');
        }

        return DB::transaction(function () use ($recon, $user): StockReconciliation {
            $recon->load('lines');
            $journalLines = [];
            foreach ($recon->lines as $line) {
                if (bccomp((string) $line->variance_qty, '0', 4) === 0 || $line->adjustment_posted) {
                    continue;
                }
                $this->inventory->adjust(
                    (int) $recon->warehouse_id,
                    (int) $line->item_id,
                    (string) $line->variance_qty,
                    $user->id,
                    ['notes' => 'Stock recon '.$recon->reconciliation_number.': '.($line->reason ?? 'Physical count')]
                );
                $line->update(['adjustment_posted' => true]);

                $unitCost = $this->estimateUnitCost((int) $line->item_id);
                $value = bcmul((string) $line->variance_qty, $unitCost, 2);
                if (bccomp($value, '0', 2) === 0) {
                    continue;
                }
                if (bccomp($value, '0', 2) > 0) {
                    $journalLines[] = ['code' => 'INV-ASSET', 'debit' => $value, 'credit' => '0.00'];
                    $journalLines[] = ['code' => 'INV-ADJUST', 'debit' => '0.00', 'credit' => $value];
                } else {
                    $abs = bcmul($value, '-1', 2);
                    $journalLines[] = ['code' => 'INV-ADJUST', 'debit' => $abs, 'credit' => '0.00'];
                    $journalLines[] = ['code' => 'INV-ASSET', 'debit' => '0.00', 'credit' => $abs];
                }
            }

            if ($journalLines !== []) {
                $this->journal->post(
                    StockReconciliation::class,
                    $recon->id,
                    'Stock reconciliation '.$recon->reconciliation_number,
                    $user->id,
                    $journalLines
                );
            }

            $recon->update([
                'status' => StockReconciliation::STATUS_POSTED,
                'posted_at' => now(),
            ]);

            return $recon->fresh(['lines.item', 'warehouse']);
        });
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
