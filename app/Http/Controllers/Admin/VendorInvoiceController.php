<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\VendorInvoice;
use App\Services\VendorInvoiceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Admin actions on vendor-uploaded tax invoices (3-way match).
 */
class VendorInvoiceController extends Controller
{
    public function __construct(
        protected VendorInvoiceService $vendorInvoices
    ) {}

    public function rematch(VendorInvoice $vendorInvoice): JsonResponse
    {
        $user = request()->user();
        if ($user === null || ! $user->can('finance.voucher.create')) {
            abort(403);
        }

        try {
            $invoice = $this->vendorInvoices->rematch($vendorInvoice, $user);

            return response()->json([
                'status' => true,
                'message' => 'Match status: '.($invoice->match_status ?? 'pending'),
                'data' => [
                    'match_status' => $invoice->match_status,
                    'match_notes' => $invoice->match_notes,
                ],
            ]);
        } catch (Throwable $e) {
            Log::error('VendorInvoiceController@rematch failed', ['message' => $e->getMessage()]);

            return response()->json([
                'status' => false,
                'message' => 'Could not re-run match.',
            ], 500);
        }
    }
}
