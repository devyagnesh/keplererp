<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PdfDocumentType;
use App\Http\Controllers\Controller;
use App\Http\Requests\ReceiveWarehouseTransferRequest;
use App\Http\Requests\StoreWarehouseTransferRequest;
use App\Models\Item;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseTransfer;
use App\Services\Pdf\PdfGeneratorService;
use App\Services\WarehouseTransferService;
use App\Support\ErpDataTable;
use App\Support\PdfDownloadLink;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Throwable;

class WarehouseTransferController extends Controller
{
    public function __construct(
        protected WarehouseTransferService $service,
        protected PdfGeneratorService $pdfGenerator
    ) {}

    public function index(): View
    {
        $this->authorize('viewAny', WarehouseTransfer::class);

        return view('admin.inventory.warehouse-transfers-index');
    }

    public function data(Request $request): JsonResponse
    {
        $this->authorize('viewAny', WarehouseTransfer::class);

        $query = WarehouseTransfer::query()
            ->select(['id', 'transfer_number', 'from_warehouse_id', 'to_warehouse_id', 'status', 'created_at'])
            ->with(['fromWarehouse:id,code,name', 'toWarehouse:id,code,name']);

        $payload = ErpDataTable::run(
            $query,
            $request,
            function ($q, string $term): void {
                $q->where('transfer_number', 'like', '%'.$term.'%')
                    ->orWhere('status', 'like', '%'.$term.'%');
            },
            ['id', 'transfer_number', 'status', 'created_at'],
        );

        $actor = $request->user();
        if ($actor === null) {
            abort(403);
        }

        $data = $payload['rows']->map(function (WarehouseTransfer $row) use ($actor) {
            return [
                'transfer_number' => $row->transfer_number,
                'from' => $row->fromWarehouse?->code ?? '—',
                'to' => $row->toWarehouse?->code ?? '—',
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

    protected function buildActionHtml(WarehouseTransfer $transfer, User $actor): string
    {
        $html = '<div class="btn-list d-flex flex-wrap gap-1">';
        $html .= '<a href="'.e(route('admin.inventory.warehouse-transfers.show', $transfer)).'" class="btn btn-sm btn-outline-primary btn-wave">View</a>';

        if ($actor->can('approve', $transfer)) {
            $html .= '<button type="button" class="btn btn-sm btn-success btn-wave js-wt-approve" data-url="'
                .e(route('admin.inventory.warehouse-transfers.approve', $transfer)).'">Approve</button>';
        }
        if ($actor->can('dispatch', $transfer)) {
            $html .= '<button type="button" class="btn btn-sm btn-warning btn-wave js-wt-dispatch" data-url="'
                .e(route('admin.inventory.warehouse-transfers.dispatch', $transfer)).'">Dispatch</button>';
        }
        if (in_array($transfer->status, [WarehouseTransfer::STATUS_IN_TRANSIT, WarehouseTransfer::STATUS_RECEIVED], true)) {
            $html .= PdfDownloadLink::button(route('admin.inventory.warehouse-transfers.pdf', $transfer), 'Challan');
        }
        $html .= '</div>';

        return $html;
    }

    public function create(): View
    {
        $this->authorize('create', WarehouseTransfer::class);

        return view('admin.inventory.warehouse-transfers-create', [
            'warehouses' => Warehouse::query()->where('is_active', true)->orderBy('code')->get(['id', 'code', 'name']),
            'items' => Item::query()->where('is_active', true)->orderBy('sku')->get(['id', 'sku', 'name']),
        ]);
    }

    public function store(StoreWarehouseTransferRequest $request): JsonResponse
    {
        $user = $request->user();
        if ($user === null) {
            abort(403);
        }

        try {
            $v = $request->validated();
            $transfer = $this->service->createDraft(
                (int) $v['from_warehouse_id'],
                (int) $v['to_warehouse_id'],
                $v['reason'] ?? null,
                $v['lines'],
                $user
            );

            return response()->json([
                'status' => true,
                'message' => 'Transfer draft created.',
                'redirect' => route('admin.inventory.warehouse-transfers.show', $transfer),
            ], 201);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 422);
        } catch (Throwable $e) {
            Log::error('WarehouseTransferController@store failed', ['message' => $e->getMessage()]);

            return response()->json(['status' => false, 'message' => 'Could not create transfer.'], 500);
        }
    }

    public function show(WarehouseTransfer $warehouseTransfer): View
    {
        $this->authorize('view', $warehouseTransfer);
        $warehouseTransfer->load(['lines.item', 'fromWarehouse', 'toWarehouse', 'creator']);

        return view('admin.inventory.warehouse-transfers-show', [
            'transfer' => $warehouseTransfer,
        ]);
    }

    public function approve(WarehouseTransfer $warehouseTransfer): JsonResponse
    {
        $this->authorize('approve', $warehouseTransfer);
        $user = request()->user();
        if ($user === null) {
            abort(403);
        }

        try {
            $this->service->approve($warehouseTransfer, $user);

            return response()->json(['status' => true, 'message' => 'Transfer approved.']);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function dispatch(Request $request, WarehouseTransfer $warehouseTransfer): JsonResponse
    {
        $this->authorize('dispatch', $warehouseTransfer);
        $user = $request->user();
        if ($user === null) {
            abort(403);
        }

        try {
            $this->service->dispatch(
                $warehouseTransfer,
                $user,
                $request->input('vehicle_no'),
                $request->input('lr_number')
            );
            $warehouseTransfer->refresh();
            $this->pdfGenerator->queue(PdfDocumentType::TransferChallan, $warehouseTransfer, $user->id);

            return response()->json(['status' => true, 'message' => 'Transfer dispatched and stock moved.']);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function receive(ReceiveWarehouseTransferRequest $request, WarehouseTransfer $warehouseTransfer): JsonResponse
    {
        $user = $request->user();
        if ($user === null) {
            abort(403);
        }

        try {
            $this->service->receive($warehouseTransfer, $user, $request->validated('lines'));

            return response()->json(['status' => true, 'message' => 'Transfer received.']);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function cancel(WarehouseTransfer $warehouseTransfer): JsonResponse
    {
        $this->authorize('cancel', $warehouseTransfer);

        try {
            $this->service->cancel($warehouseTransfer);

            return response()->json(['status' => true, 'message' => 'Transfer cancelled.']);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function downloadPdf(Request $request, WarehouseTransfer $warehouseTransfer): Response
    {
        $this->authorize('view', $warehouseTransfer);

        return $this->pdfGenerator->downloadOrGenerate(
            PdfDocumentType::TransferChallan,
            $warehouseTransfer,
            $request->user()?->id
        );
    }
}
