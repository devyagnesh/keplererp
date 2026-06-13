<?php

namespace App\Http\Controllers\VendorPortal;

use App\Enums\VendorStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\VendorPortal\ChangePortalPasswordRequest;
use App\Http\Requests\VendorPortal\StoreVendorInvoiceRequest;
use App\Models\Payment;
use App\Models\PurchaseOrder;
use App\Models\Vendor;
use App\Models\VendorPayable;
use App\Services\VendorInvoiceService;
use App\Services\WhatsApp\WhatsAppNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;
use Throwable;

/**
 * Vendor self-service portal (SRS US-11).
 */
class PortalAuthController extends Controller
{
    public function __construct(
        protected VendorInvoiceService $vendorInvoices
    ) {}

    public function showLogin(): View
    {
        return view('vendor-portal.login');
    }

    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $vendor = Vendor::query()
            ->where('portal_enabled', true)
            ->where('email', $request->string('email')->toString())
            ->first();

        if ($vendor === null
            || $vendor->status !== VendorStatus::Active
            || ! $vendor->portal_enabled
            || $vendor->portal_password === null
            || ! Hash::check($request->string('password')->toString(), $vendor->portal_password)) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid portal credentials.',
            ], 422);
        }

        Auth::guard('vendor')->login($vendor);

        $redirect = $vendor->portal_must_change_password
            ? route('vendor.portal.change-password')
            : route('vendor.portal.dashboard');

        return response()->json([
            'status' => true,
            'message' => 'Logged in successfully.',
            'redirect' => $redirect,
        ]);
    }

    public function showChangePassword(): View
    {
        return view('vendor-portal.change-password');
    }

    public function changePassword(ChangePortalPasswordRequest $request): JsonResponse
    {
        $vendor = Auth::guard('vendor')->user();
        if (! $vendor instanceof Vendor) {
            abort(403);
        }

        if (! Hash::check($request->string('current_password')->toString(), (string) $vendor->portal_password)) {
            return response()->json([
                'status' => false,
                'message' => 'Current password is incorrect.',
            ], 422);
        }

        $vendor->update([
            'portal_password' => $request->string('password')->toString(),
            'portal_must_change_password' => false,
            'portal_password_changed_at' => now(),
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Password changed successfully.',
            'redirect' => route('vendor.portal.dashboard'),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        Auth::guard('vendor')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json([
            'status' => true,
            'message' => 'Logged out.',
            'redirect' => route('vendor.portal.login'),
        ]);
    }

    public function dashboard(): View
    {
        $vendor = Auth::guard('vendor')->user();
        if (! $vendor instanceof Vendor) {
            abort(403);
        }

        $orders = PurchaseOrder::query()
            ->where('vendor_id', $vendor->id)
            ->whereIn('status', ['approved', 'sent', 'partial', 'received', 'accepted'])
            ->orderByDesc('id')
            ->limit(25)
            ->get(['id', 'po_number', 'order_date', 'status', 'total_amount']);

        $payables = VendorPayable::query()
            ->with(['goodsReceipt:id,grn_number', 'vendorInvoices:id,vendor_payable_id,vendor_invoice_number,match_status,amount'])
            ->where('vendor_id', $vendor->id)
            ->whereIn('status', ['open', 'partial', 'paid'])
            ->orderByDesc('id')
            ->limit(25)
            ->get();

        $payments = Payment::query()
            ->where('vendor_id', $vendor->id)
            ->where('payment_type', 'vendor')
            ->orderByDesc('payment_date')
            ->limit(20)
            ->get(['id', 'payment_number', 'amount', 'payment_date', 'payment_method', 'utr_reference']);

        return view('vendor-portal.dashboard', [
            'vendor' => $vendor,
            'orders' => $orders,
            'payables' => $payables,
            'payments' => $payments,
        ]);
    }

    public function updateDelivery(Request $request, PurchaseOrder $purchaseOrder): JsonResponse
    {
        $vendor = Auth::guard('vendor')->user();
        if (! $vendor instanceof Vendor || (int) $purchaseOrder->vendor_id !== (int) $vendor->id) {
            abort(403);
        }

        $data = $request->validate([
            'vendor_delivery_status' => ['required', 'in:in_transit,delivered,delayed'],
            'vendor_delivery_notes' => ['nullable', 'string', 'max:500'],
        ]);

        $purchaseOrder->update([
            'vendor_delivery_status' => $data['vendor_delivery_status'],
            'vendor_delivery_notes' => $data['vendor_delivery_notes'] ?? null,
            'vendor_delivery_updated_at' => now(),
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Delivery status updated.',
        ]);
    }

    public function storeVendorInvoice(StoreVendorInvoiceRequest $request): JsonResponse
    {
        $vendor = Auth::guard('vendor')->user();
        if (! $vendor instanceof Vendor) {
            abort(403);
        }

        try {
            $payable = VendorPayable::query()->findOrFail((int) $request->validated('vendor_payable_id'));
            $file = $request->file('document');
            if (! $file instanceof UploadedFile) {
                return response()->json([
                    'status' => false,
                    'message' => 'Invoice file is required.',
                ], 422);
            }

            $invoice = $this->vendorInvoices->storeFromPortal(
                $vendor,
                $payable,
                $file,
                (string) $request->validated('vendor_invoice_number'),
                (string) $request->validated('invoice_date'),
                (string) $request->validated('amount')
            );
            $invoice->update(['uploaded_by_vendor' => $vendor->id]);

            return response()->json([
                'status' => true,
                'message' => 'Invoice uploaded. Match status: '.($invoice->match_status ?? 'pending'),
            ], 201);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => 'Could not upload invoice.',
            ], 500);
        }
    }

    public function acceptPo(PurchaseOrder $purchaseOrder): JsonResponse
    {
        $vendor = Auth::guard('vendor')->user();
        if (! $vendor instanceof Vendor || (int) $purchaseOrder->vendor_id !== (int) $vendor->id) {
            abort(403);
        }
        if ($purchaseOrder->status !== 'sent') {
            return response()->json([
                'status' => false,
                'message' => 'Only sent POs can be accepted.',
            ], 422);
        }
        $purchaseOrder->update(['status' => 'accepted']);
        $purchaseOrder->loadMissing('creator');
        app(WhatsAppNotificationService::class)->notifyVendorPoAccepted($purchaseOrder);

        return response()->json([
            'status' => true,
            'message' => 'Purchase order accepted.',
        ]);
    }

    public function rejectPo(Request $request, PurchaseOrder $purchaseOrder): JsonResponse
    {
        $vendor = Auth::guard('vendor')->user();
        if (! $vendor instanceof Vendor || (int) $purchaseOrder->vendor_id !== (int) $vendor->id) {
            abort(403);
        }
        if ($purchaseOrder->status !== 'sent') {
            return response()->json([
                'status' => false,
                'message' => 'Only sent POs can be rejected.',
            ], 422);
        }
        $purchaseOrder->update([
            'status' => 'rejected',
            'rejected_reason' => (string) $request->input('rejected_reason', 'Rejected via vendor portal'),
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Purchase order rejected.',
        ]);
    }
}
