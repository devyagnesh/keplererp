<?php

namespace App\Services;

use App\Models\GoodsReceipt;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorInvoice;
use App\Models\VendorPayable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Vendor tax invoice uploads and 3-way match against GRN payables.
 */
class VendorInvoiceService
{
    public function __construct(
        protected ThreeWayMatchService $matchService
    ) {}

    public function storeFromPortal(
        Vendor $vendor,
        VendorPayable $payable,
        UploadedFile $file,
        string $vendorInvoiceNumber,
        string $invoiceDate,
        string $amount
    ): VendorInvoice {
        if ((int) $payable->vendor_id !== (int) $vendor->id) {
            throw new InvalidArgumentException('Payable does not belong to this vendor.');
        }
        if (! in_array($payable->status, ['open', 'partial'], true)) {
            throw new InvalidArgumentException('Payable is not open for invoice upload.');
        }

        if (! in_array($file->getMimeType(), ['application/pdf', 'image/jpeg', 'image/png'], true)) {
            throw new InvalidArgumentException('Only PDF, JPEG, or PNG files are allowed.');
        }
        if ($file->getSize() > 5 * 1024 * 1024) {
            throw new InvalidArgumentException('File size must not exceed 5 MB.');
        }

        return DB::transaction(function () use ($vendor, $payable, $file, $vendorInvoiceNumber, $invoiceDate, $amount): VendorInvoice {
            $path = $file->store('vendor-invoices/'.$vendor->id, 'local');
            $payable->loadMissing('goodsReceipt.purchaseOrder');
            $grn = $payable->goodsReceipt;
            $poAmount = $grn?->purchaseOrder?->total_amount;
            $grnAmount = $payable->amount;

            $invoice = VendorInvoice::query()->create([
                'vendor_id' => $vendor->id,
                'vendor_payable_id' => $payable->id,
                'vendor_invoice_number' => $vendorInvoiceNumber,
                'invoice_date' => $invoiceDate,
                'amount' => $amount,
                'original_name' => $file->getClientOriginalName(),
                'storage_path' => $path,
                'mime_type' => $file->getMimeType(),
                'size_bytes' => $file->getSize(),
                'status' => 'uploaded',
                'po_amount' => $poAmount,
                'grn_amount' => $grnAmount,
            ]);

            $this->matchService->evaluate($invoice);

            return $invoice->fresh();
        });
    }

    public function rematch(VendorInvoice $invoice, User $user): VendorInvoice
    {
        return DB::transaction(function () use ($invoice, $user): VendorInvoice {
            $this->matchService->evaluate($invoice);
            $invoice->update([
                'matched_by' => $user->id,
                'matched_at' => now(),
            ]);

            return $invoice->fresh();
        });
    }
}
