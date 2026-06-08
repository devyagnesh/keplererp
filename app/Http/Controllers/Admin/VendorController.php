<?php

namespace App\Http\Controllers\Admin;

use App\Enums\VendorStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreVendorDocumentRequest;
use App\Http\Requests\StoreVendorRequest;
use App\Http\Requests\UpdateVendorRequest;
use App\Models\VendorDocument;
use App\Models\User;
use App\Models\Vendor;
use App\Repositories\Contracts\VendorRepositoryInterface;
use App\Services\AuditLogService;
use App\Services\VendorDocumentService;
use App\Services\VendorService;
use App\Services\VendorPortalNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Throwable;

/**
 * Supplier (vendor) master — list, CRUD, approvals.
 */
class VendorController extends Controller
{
    public function __construct(
        protected VendorRepositoryInterface $vendors,
        protected VendorService $vendorService,
        protected VendorPortalNotificationService $vendorPortalNotifications,
        protected AuditLogService $auditLog,
        protected VendorDocumentService $vendorDocuments
    ) {}

    /**
     * Vendors listing (DataTables server-side).
     */
    public function index(): View
    {
        $this->authorize('viewAny', Vendor::class);

        return view('admin.vendors.index');
    }

    /**
     * DataTables JSON for vendors.
     */
    public function data(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Vendor::class);

        $payload = $this->vendors->getDataTableRows($request);
        $actor = $request->user();
        if ($actor === null) {
            abort(403);
        }

        $data = $payload['rows']->map(function (Vendor $vendor) use ($actor) {
            return [
                'vendor_code' => $vendor->vendor_code,
                'name' => $vendor->name,
                'phone' => $vendor->phone,
                'gstin' => $vendor->gstin ?? '—',
                'city' => $vendor->city,
                'status' => $vendor->status->label(),
                'created_at' => $vendor->created_at?->format('Y-m-d H:i'),
                'action' => $this->buildActionHtml($vendor, $actor),
            ];
        })->values()->all();

        return response()->json([
            'draw' => $payload['draw'],
            'recordsTotal' => $payload['recordsTotal'],
            'recordsFiltered' => $payload['recordsFiltered'],
            'data' => $data,
        ]);
    }

    /**
     * Build action buttons for the current row.
     */
    protected function buildActionHtml(Vendor $vendor, User $actor): string
    {
        $html = '<div class="btn-list d-flex flex-wrap gap-1">';

        if ($actor->can('update', $vendor)) {
            $html .= '<a href="'.e(route('admin.vendors.edit', $vendor)).'" class="btn btn-sm btn-primary btn-wave">Edit</a>';
        }

        if ($actor->can('approve', $vendor)) {
            $html .= '<button type="button" class="btn btn-sm btn-success btn-wave js-vendor-approve" data-url="'
                .e(route('admin.vendors.approve', $vendor)).'">Approve</button>';
        }

        if ($actor->can('delete', $vendor)) {
            $html .= '<button type="button" class="btn btn-sm btn-danger btn-wave js-vendor-delete" data-url="'
                .e(route('admin.vendors.destroy', $vendor)).'">Delete</button>';
        }

        if ($actor->can('finance.reports.view')) {
            $html .= \App\Support\PdfDownloadLink::button(
                route('admin.reports.vendor-statement.pdf', $vendor),
                'Statement'
            );
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * Show create vendor form.
     */
    public function create(): View
    {
        $this->authorize('create', Vendor::class);

        return view('admin.vendors.create', [
            'gstStates' => config('gst.state_codes', []),
        ]);
    }

    /**
     * Store a new vendor (pending approval).
     */
    public function store(StoreVendorRequest $request): JsonResponse
    {
        try {
            $actor = $request->user();
            if ($actor === null) {
                abort(403);
            }

            $validated = $request->validated();
            $vendor = $this->vendorService->create($validated, $actor);

            return response()->json([
                'status' => true,
                'message' => 'Vendor created successfully. Awaiting approval.',
                'data' => ['id' => $vendor->id],
            ], 201);
        } catch (Throwable $e) {
            Log::error('VendorController@store failed', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Could not create vendor.',
            ], 500);
        }
    }

    /**
     * Show edit vendor form.
     */
    public function edit(Vendor $vendor): View
    {
        $this->authorize('update', $vendor);

        return view('admin.vendors.edit', [
            'vendor' => $vendor,
            'gstStates' => config('gst.state_codes', []),
            'documents' => $vendor->documents()->latest()->get(),
        ]);
    }

    /**
     * Update vendor master fields.
     */
    public function update(UpdateVendorRequest $request, Vendor $vendor): JsonResponse
    {
        try {
            $validated = $request->validated();
            $result = $this->vendorService->update($vendor, $validated);
            $vendor = $result['vendor'];
            $portalPlain = $result['portal_plain_password'];
            $message = 'Vendor updated successfully.';
            if ($portalPlain !== null && $vendor->portal_enabled) {
                $emailed = $this->vendorPortalNotifications->sendPortalCredentials(
                    $vendor,
                    $portalPlain,
                    route('vendor.portal.login')
                );
                $this->auditLog->record(
                    'vendor.portal_enabled',
                    'Vendor portal access issued for '.$vendor->vendor_code,
                    $vendor,
                    $request->user()
                );
                $message = $emailed
                    ? 'Vendor updated successfully. Portal credentials sent by email.'
                    : 'Vendor updated successfully. Add a vendor email address to send portal credentials.';
            }

            return response()->json([
                'status' => true,
                'message' => $message,
            ]);
        } catch (Throwable $e) {
            Log::error('VendorController@update failed', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'vendor_id' => $vendor->id,
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Could not update vendor.',
            ], 500);
        }
    }

    /**
     * Approve a pending vendor.
     */
    public function approve(Vendor $vendor): JsonResponse
    {
        $this->authorize('approve', $vendor);

        $actor = request()->user();
        if ($actor === null) {
            abort(403);
        }

        try {
            $this->vendorService->approve($vendor, $actor);

            return response()->json([
                'status' => true,
                'message' => 'Vendor approved and activated.',
            ]);
        } catch (Throwable $e) {
            Log::error('VendorController@approve failed', [
                'message' => $e->getMessage(),
                'vendor_id' => $vendor->id,
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Could not approve vendor.',
            ], 500);
        }
    }

    /**
     * Block an active vendor.
     */
    public function block(Vendor $vendor): JsonResponse
    {
        $this->authorize('block', $vendor);

        try {
            $this->vendorService->block($vendor);

            return response()->json([
                'status' => true,
                'message' => 'Vendor blocked.',
            ]);
        } catch (Throwable $e) {
            Log::error('VendorController@block failed', [
                'message' => $e->getMessage(),
                'vendor_id' => $vendor->id,
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Could not block vendor.',
            ], 500);
        }
    }

    /**
     * Reactivate a blocked vendor.
     */
    public function activate(Vendor $vendor): JsonResponse
    {
        $this->authorize('activate', $vendor);

        try {
            $this->vendorService->activate($vendor);

            return response()->json([
                'status' => true,
                'message' => 'Vendor reactivated.',
            ]);
        } catch (Throwable $e) {
            Log::error('VendorController@activate failed', [
                'message' => $e->getMessage(),
                'vendor_id' => $vendor->id,
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Could not reactivate vendor.',
            ], 500);
        }
    }

    public function storeDocument(StoreVendorDocumentRequest $request, Vendor $vendor): JsonResponse
    {
        $user = $request->user();
        if ($user === null) {
            abort(403);
        }

        try {
            $file = $request->file('document');
            if (! $file instanceof UploadedFile) {
                throw new \InvalidArgumentException('Document file is required.');
            }
            $doc = $this->vendorDocuments->store(
                $vendor,
                $file,
                (string) $request->validated('document_type'),
                $user
            );
            $this->auditLog->record(
                'vendor.document_uploaded',
                'Uploaded '.$doc->document_type.' for vendor '.$vendor->vendor_code,
                $doc,
                $user
            );

            return response()->json([
                'status' => true,
                'message' => 'Document uploaded successfully.',
            ], 201);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (Throwable $e) {
            Log::error('VendorController@storeDocument failed', ['message' => $e->getMessage()]);

            return response()->json([
                'status' => false,
                'message' => 'Could not upload document.',
            ], 500);
        }
    }

    public function destroyDocument(Vendor $vendor, VendorDocument $vendorDocument): JsonResponse
    {
        $this->authorize('update', $vendor);
        if ((int) $vendorDocument->vendor_id !== (int) $vendor->id) {
            abort(404);
        }

        try {
            $this->vendorDocuments->delete($vendorDocument);
            $this->auditLog->record(
                'vendor.document_deleted',
                'Deleted document '.$vendorDocument->document_type.' for vendor '.$vendor->vendor_code,
                $vendor,
                request()->user()
            );

            return response()->json([
                'status' => true,
                'message' => 'Document deleted.',
            ]);
        } catch (Throwable $e) {
            Log::error('VendorController@destroyDocument failed', ['message' => $e->getMessage()]);

            return response()->json([
                'status' => false,
                'message' => 'Could not delete document.',
            ], 500);
        }
    }

    /**
     * Soft-delete vendor.
     */
    public function destroy(Vendor $vendor): JsonResponse
    {
        $this->authorize('delete', $vendor);

        try {
            $this->vendorService->delete($vendor);

            return response()->json([
                'status' => true,
                'message' => 'Vendor deleted successfully.',
            ]);
        } catch (Throwable $e) {
            Log::error('VendorController@destroy failed', [
                'message' => $e->getMessage(),
                'vendor_id' => $vendor->id,
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Could not delete vendor.',
            ], 500);
        }
    }
}
