<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\IssuesDocumentNumbers;
use App\Http\Controllers\Controller;
use App\Http\Requests\StorePurchaseRequisitionRequest;
use App\Models\Item;
use App\Models\PurchaseRequisition;
use App\Models\User;
use App\Services\PurchaseRequisitionConversionService;
use App\Services\WhatsApp\WhatsAppNotificationService;
use App\Support\ErpDataTable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Throwable;

class PurchaseRequisitionController extends Controller
{
    use IssuesDocumentNumbers;

    public function __construct(
        protected WhatsAppNotificationService $whatsappNotifications,
        protected PurchaseRequisitionConversionService $prConversion
    ) {}

    public function index(): View
    {
        $this->authorize('viewAny', PurchaseRequisition::class);

        return view('admin.purchase.requisitions-index');
    }

    public function data(Request $request): JsonResponse
    {
        $this->authorize('viewAny', PurchaseRequisition::class);

        $query = PurchaseRequisition::query()->select([
            'id', 'pr_number', 'required_date', 'status', 'warehouse_id', 'requested_by', 'created_at',
        ]);

        $payload = ErpDataTable::run(
            $query,
            $request,
            function ($q, string $term): void {
                $q->where('pr_number', 'like', '%'.$term.'%')
                    ->orWhere('status', 'like', '%'.$term.'%');
            },
            ['id', 'pr_number', 'required_date', 'status', 'warehouse_id', 'created_at'],
        );

        $actor = $request->user();
        if ($actor === null) {
            abort(403);
        }

        $data = $payload['rows']->map(function (PurchaseRequisition $row) use ($actor) {
            return [
                'pr_number' => $row->pr_number,
                'required_date' => $row->required_date?->format('Y-m-d') ?? '—',
                'status' => $row->status,
                'created_at' => $row->created_at?->format('Y-m-d H:i'),
                'action' => $this->buildActionHtml($row, $actor),
            ];
        })->values()->all();

        return response()->json([
            'draw' => $payload['draw'],
            'recordsTotal' => $payload['recordsTotal'],
            'recordsFiltered' => $payload['recordsFiltered'],
            'data' => $data,
        ]);
    }

    protected function buildActionHtml(PurchaseRequisition $pr, User $actor): string
    {
        $html = '<div class="btn-list d-flex flex-wrap gap-1">';
        if ($actor->can('submit', $pr)) {
            $html .= '<button type="button" class="btn btn-sm btn-primary btn-wave js-pr-post" data-confirm="Submit for approval?" data-url="'
                .e(route('admin.purchase.requisitions.submit', $pr)).'">Submit</button>';
        }
        if ($actor->can('approve', $pr)) {
            $html .= '<button type="button" class="btn btn-sm btn-success btn-wave js-pr-post" data-confirm="Approve this PR?" data-url="'
                .e(route('admin.purchase.requisitions.approve', $pr)).'">Approve</button>';
        }
        if ($actor->can('reject', $pr)) {
            $html .= '<button type="button" class="btn btn-sm btn-warning btn-wave js-pr-reject" data-url="'
                .e(route('admin.purchase.requisitions.reject', $pr)).'">Reject</button>';
        }
        if ($actor->can('convert', $pr)) {
            $html .= '<button type="button" class="btn btn-sm btn-outline-primary btn-wave js-pr-convert" data-confirm="Convert to purchase order?" data-url="'
                .e(route('admin.purchase.requisitions.convert', $pr)).'">To PO</button>';
        }
        if ($actor->can('delete', $pr)) {
            $html .= '<button type="button" class="btn btn-sm btn-danger btn-wave js-pr-delete" data-url="'
                .e(route('admin.purchase.requisitions.destroy', $pr)).'">Delete</button>';
        }
        $html .= '</div>';

        return $html;
    }

    public function create(): View
    {
        $this->authorize('create', PurchaseRequisition::class);

        return view('admin.purchase.requisitions-create', [
            'items' => Item::query()->where('is_active', true)->orderBy('sku')->get(['id', 'sku', 'name']),
            'warehouses' => \App\Models\Warehouse::query()->where('is_active', true)->orderBy('code')->get(['id', 'code', 'name']),
        ]);
    }

    public function store(StorePurchaseRequisitionRequest $request): JsonResponse
    {
        $user = $request->user();
        if ($user === null) {
            abort(403);
        }

        try {
            DB::transaction(function () use ($request, $user): void {
                $pr = PurchaseRequisition::query()->create([
                    'pr_number' => $this->nextDocumentCode('purchase_requisitions', 'PR-'),
                    'required_date' => $request->validated('required_date'),
                    'warehouse_id' => $request->validated('warehouse_id'),
                    'status' => 'draft',
                    'requested_by' => $user->id,
                    'notes' => $request->validated('notes'),
                ]);
                foreach ($request->validated('lines') as $line) {
                    $pr->lines()->create([
                        'item_id' => $line['item_id'],
                        'quantity' => $line['quantity'],
                    ]);
                }
            });

            return response()->json([
                'status' => true,
                'message' => 'Purchase requisition created successfully.',
            ], 201);
        } catch (Throwable $e) {
            Log::error('PurchaseRequisitionController@store failed', ['message' => $e->getMessage()]);

            return response()->json([
                'status' => false,
                'message' => 'Could not create purchase requisition.',
            ], 500);
        }
    }

    public function submit(PurchaseRequisition $purchaseRequisition): JsonResponse
    {
        $this->authorize('submit', $purchaseRequisition);

        try {
            $purchaseRequisition->update(['status' => 'pending_approval']);

            return response()->json([
                'status' => true,
                'message' => 'Requisition submitted for approval.',
            ]);
        } catch (Throwable $e) {
            Log::error('PurchaseRequisitionController@submit failed', ['message' => $e->getMessage()]);

            return response()->json([
                'status' => false,
                'message' => 'Could not submit requisition.',
            ], 500);
        }
    }

    public function approve(Request $request, PurchaseRequisition $purchaseRequisition): JsonResponse
    {
        $this->authorize('approve', $purchaseRequisition);

        $user = $request->user();
        if ($user === null) {
            abort(403);
        }

        try {
            $purchaseRequisition->update([
                'status' => 'approved',
                'approved_by' => $user->id,
                'approved_at' => now(),
                'rejected_reason' => null,
            ]);
            $purchaseRequisition->refresh();
            $this->whatsappNotifications->notifyPurchaseRequisitionApproved($purchaseRequisition);

            return response()->json([
                'status' => true,
                'message' => 'Purchase requisition approved.',
            ]);
        } catch (Throwable $e) {
            Log::error('PurchaseRequisitionController@approve failed', ['message' => $e->getMessage()]);

            return response()->json([
                'status' => false,
                'message' => 'Could not approve requisition.',
            ], 500);
        }
    }

    public function reject(Request $request, PurchaseRequisition $purchaseRequisition): JsonResponse
    {
        $this->authorize('reject', $purchaseRequisition);

        $data = $request->validate([
            'rejected_reason' => ['required', 'string', 'max:5000'],
        ]);

        try {
            $purchaseRequisition->update([
                'status' => 'rejected',
                'rejected_reason' => $data['rejected_reason'],
            ]);
            $purchaseRequisition->refresh();
            $this->whatsappNotifications->notifyPurchaseRequisitionRejected($purchaseRequisition);

            return response()->json([
                'status' => true,
                'message' => 'Purchase requisition rejected.',
            ]);
        } catch (Throwable $e) {
            Log::error('PurchaseRequisitionController@reject failed', ['message' => $e->getMessage()]);

            return response()->json([
                'status' => false,
                'message' => 'Could not reject requisition.',
            ], 500);
        }
    }

    public function convert(Request $request, PurchaseRequisition $purchaseRequisition): JsonResponse
    {
        $this->authorize('approve', $purchaseRequisition);

        $user = $request->user();
        if ($user === null) {
            abort(403);
        }

        $data = $request->validate([
            'vendor_id' => ['required', 'integer', 'exists:vendors,id'],
        ]);

        try {
            $po = $this->prConversion->convertToPurchaseOrder(
                $purchaseRequisition,
                $user,
                (int) $data['vendor_id']
            );

            return response()->json([
                'status' => true,
                'message' => 'Purchase requisition converted to PO '.$po->po_number.'.',
                'data' => ['po_id' => $po->id, 'po_number' => $po->po_number],
            ], 201);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 422);
        } catch (Throwable $e) {
            Log::error('PurchaseRequisitionController@convert failed', ['message' => $e->getMessage()]);

            return response()->json(['status' => false, 'message' => 'Could not convert requisition.'], 500);
        }
    }

    public function destroy(PurchaseRequisition $purchaseRequisition): JsonResponse
    {
        $this->authorize('delete', $purchaseRequisition);

        try {
            $purchaseRequisition->delete();

            return response()->json([
                'status' => true,
                'message' => 'Purchase requisition deleted.',
            ]);
        } catch (Throwable $e) {
            Log::error('PurchaseRequisitionController@destroy failed', ['message' => $e->getMessage()]);

            return response()->json([
                'status' => false,
                'message' => 'Could not delete purchase requisition.',
            ], 500);
        }
    }
}
