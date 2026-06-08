<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\IssuesDocumentNumbers;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCustomerReceiptRequest;
use App\Http\Requests\StoreVendorPaymentRequest;
use App\Models\Invoice;
use App\Models\VendorInvoice;
use App\Models\VendorPayable;
use App\Services\PaymentService;
use App\Support\ErpDataTable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Throwable;

/**
 * Vendor payments and customer receipts (SRS finance cycle).
 */
class FinancePaymentController extends Controller
{
    use IssuesDocumentNumbers;

    public function __construct(
        protected PaymentService $payments
    ) {}

    public function index(): View
    {
        $this->authorize('viewAny', \App\Models\Payment::class);

        return view('admin.finance.payments-index', [
            'openPayables' => VendorPayable::query()
                ->with('vendor:id,name,vendor_code')
                ->whereIn('status', ['open', 'partial'])
                ->orderByDesc('id')
                ->limit(50)
                ->get(),
            'openInvoices' => Invoice::query()
                ->with('customer:id,name,customer_code')
                ->whereIn('status', ['posted', 'partially_paid'])
                ->orderByDesc('id')
                ->limit(50)
                ->get(),
            'vendorInvoices' => VendorInvoice::query()
                ->with(['vendor:id,name,vendor_code', 'vendorPayable.goodsReceipt:id,grn_number'])
                ->orderByDesc('id')
                ->limit(30)
                ->get(),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $this->authorize('viewAny', \App\Models\Payment::class);

        $query = \App\Models\Payment::query()
            ->select(['id', 'payment_number', 'payment_type', 'amount', 'payment_date', 'created_at']);

        $payload = ErpDataTable::run(
            $query,
            $request,
            function ($q, string $term): void {
                $q->where('payment_number', 'like', '%'.$term.'%');
            },
            ['id', 'payment_number', 'payment_date', 'created_at'],
        );

        $data = $payload['rows']->map(function (\App\Models\Payment $row) {
            return [
                'payment_number' => $row->payment_number,
                'payment_type' => $row->payment_type,
                'amount' => (string) $row->amount,
                'payment_date' => $row->payment_date?->format('Y-m-d'),
                'created_at' => $row->created_at?->format('Y-m-d H:i'),
            ];
        })->values()->all();

        return response()->json([
            'draw' => $payload['draw'],
            'recordsTotal' => $payload['recordsTotal'],
            'recordsFiltered' => $payload['recordsFiltered'],
            'data' => $data,
        ]);
    }

    public function storeVendorPayment(StoreVendorPaymentRequest $request): JsonResponse
    {
        $user = $request->user();
        if ($user === null) {
            abort(403);
        }

        try {
            $this->payments->recordVendorPayment(
                $request->validated(),
                $user,
                $this->nextDocumentCode('payments', 'PAY-')
            );

            return response()->json([
                'status' => true,
                'message' => 'Vendor payment recorded successfully.',
            ], 201);
        } catch (Throwable $e) {
            Log::error('FinancePaymentController@storeVendorPayment failed', ['message' => $e->getMessage()]);

            return response()->json([
                'status' => false,
                'message' => $e->getMessage() ?: 'Could not record payment.',
            ], 422);
        }
    }

    public function storeCustomerReceipt(StoreCustomerReceiptRequest $request): JsonResponse
    {
        $user = $request->user();
        if ($user === null) {
            abort(403);
        }

        try {
            $this->payments->recordCustomerReceipt(
                $request->validated(),
                $user,
                $this->nextDocumentCode('payments', 'RCT-')
            );

            return response()->json([
                'status' => true,
                'message' => 'Customer receipt recorded successfully.',
            ], 201);
        } catch (Throwable $e) {
            Log::error('FinancePaymentController@storeCustomerReceipt failed', ['message' => $e->getMessage()]);

            return response()->json([
                'status' => false,
                'message' => $e->getMessage() ?: 'Could not record receipt.',
            ], 422);
        }
    }
}
