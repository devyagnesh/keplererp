<?php

namespace App\Services;

use App\Models\DebitNote;
use App\Models\GoodsReceipt;
use App\Models\GrnReturn;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Throwable;

/**
 * GRN return posting with optional vendor debit note (SRS purchase returns).
 */
class GrnReturnService
{
    public function __construct(
        protected InventoryStockService $stockService,
        protected DocumentNumberService $documentNumbers
    ) {}

    /**
     * @param  list<array{item_id: int, quantity: string, batch_no?: string|null}>  $lines
     *
     * @throws Throwable
     */
    public function createAndPost(
        GoodsReceipt $grn,
        array $lines,
        ?string $reason,
        string $debitAmount,
        User $user
    ): GrnReturn {
        if ($grn->status !== 'posted') {
            throw new InvalidArgumentException('Returns are only allowed against posted GRNs.');
        }

        return DB::transaction(function () use ($grn, $lines, $reason, $debitAmount, $user): GrnReturn {
            $return = GrnReturn::query()->create([
                'return_number' => $this->documentNumbers->next('grn_returns', 'GRN-RET-'),
                'goods_receipt_id' => $grn->id,
                'vendor_id' => $grn->vendor_id,
                'status' => 'posted',
                'reason' => $reason,
                'created_by' => $user->id,
                'posted_at' => now(),
            ]);

            foreach ($lines as $line) {
                $return->lines()->create([
                    'item_id' => (int) $line['item_id'],
                    'quantity' => (string) $line['quantity'],
                    'batch_no' => $line['batch_no'] ?? null,
                ]);
            }

            $return->load('lines');
            $this->stockService->applyGrnReturn($return, $user->id);

            if (bccomp($debitAmount, '0', 2) > 0) {
                DebitNote::query()->create([
                    'debit_note_number' => $this->documentNumbers->next('debit_notes', 'DN-'),
                    'vendor_id' => $grn->vendor_id,
                    'goods_receipt_id' => $grn->id,
                    'grn_return_id' => $return->id,
                    'amount' => $debitAmount,
                    'status' => 'posted',
                    'reason' => $reason,
                    'created_by' => $user->id,
                ]);
            }

            return $return;
        });
    }
}
