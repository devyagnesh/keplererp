<?php

namespace App\Services;

use App\Enums\PdfDocumentType;
use App\Models\GoodsReceipt;
use App\Models\User;
use App\Services\Pdf\PdfGeneratorService;
use App\Services\WhatsApp\WhatsAppNotificationService;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Throwable;

/**
 * Posts draft GRNs to inventory and accounts (SRS UC 22.1 step 8).
 */
class GoodsReceiptPostService
{
    public function __construct(
        protected InventoryStockService $stockService,
        protected GoodsReceiptAccountingService $grnAccounting,
        protected WhatsAppNotificationService $whatsappNotifications,
        protected PdfGeneratorService $pdfGenerator
    ) {}

    /**
     * @throws Throwable
     */
    public function post(GoodsReceipt $grn, User $user): GoodsReceipt
    {
        if ($grn->status !== 'draft') {
            throw new InvalidArgumentException('Only draft goods receipts can be posted.');
        }

        $grn->loadMissing(['lines', 'purchaseOrder']);
        $po = $grn->purchaseOrder;
        if ($po === null || ! in_array($po->status, ['approved', 'sent', 'accepted', 'partial'], true)) {
            throw new InvalidArgumentException('Linked purchase order must be approved before posting GRN.');
        }

        DB::transaction(function () use ($grn, $user): void {
            $stockLines = [];
            foreach ($grn->lines as $line) {
                $stockLines[] = [
                    'item_id' => $line->item_id,
                    'accepted_qty' => (string) ($line->accepted_qty ?? $line->quantity),
                    'batch_no' => $line->batch_no,
                    'serial_no' => $line->serial_no,
                    'expiry_date' => $line->expiry_date?->toDateString(),
                ];
            }
            $this->stockService->applyGoodsReceipt($grn, $stockLines, $user->id);
            $grn->load('lines');
            $this->grnAccounting->postPayableAndJournal($grn, $user->id);
            $grn->update([
                'status' => 'posted',
                'posted_at' => now(),
            ]);
        });

        $fresh = $grn->fresh();
        $this->whatsappNotifications->notifyGrnPosted($fresh);
        $this->pdfGenerator->queue(PdfDocumentType::Grn, $fresh, $user->id);

        return $fresh;
    }
}
