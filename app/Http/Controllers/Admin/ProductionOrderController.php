<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\IssuesDocumentNumbers;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductionOrderRequest;
use App\Http\Requests\UpdateProductionOrderRequest;
use App\Models\BillOfMaterial;
use App\Models\Item;
use App\Models\ProductionOrder;
use App\Models\User;
use App\Support\ErpDataTable;
use App\Support\PdfDownloadLink;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Services\AuditLogService;
use App\Services\ProductionReleaseService;
use App\Enums\PdfDocumentType;
use App\Services\InventoryStockService;
use App\Services\Pdf\PdfGeneratorService;
use App\Services\WhatsApp\WhatsAppNotificationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Throwable;

class ProductionOrderController extends Controller
{
    use IssuesDocumentNumbers;

    public function __construct(
        protected InventoryStockService $stockService,
        protected WhatsAppNotificationService $whatsapp,
        protected AuditLogService $auditLog,
        protected PdfGeneratorService $pdfGenerator,
        protected ProductionReleaseService $productionRelease
    ) {}

    public function index(): View
    {
        $this->authorize('viewAny', ProductionOrder::class);

        return view('admin.production.work-orders-index');
    }

    public function data(Request $request): JsonResponse
    {
        $this->authorize('viewAny', ProductionOrder::class);

        $query = ProductionOrder::query()
            ->select(['production_orders.id', 'wo_number', 'item_id', 'qty_planned', 'status', 'planned_start', 'created_at'])
            ->with(['item:id,sku,name']);

        $payload = ErpDataTable::run(
            $query,
            $request,
            function ($q, string $term): void {
                $q->where('wo_number', 'like', '%'.$term.'%')
                    ->orWhereHas('item', function ($iq) use ($term): void {
                        $iq->where('sku', 'like', '%'.$term.'%');
                    });
            },
            ['id', 'wo_number', 'qty_planned', 'status', 'created_at'],
        );

        $actor = $request->user();
        if ($actor === null) {
            abort(403);
        }

        $data = $payload['rows']->map(function (ProductionOrder $row) use ($actor) {
            return [
                'wo_number' => $row->wo_number,
                'item' => $row->item?->display_label ?? '—',
                'qty_planned' => (string) $row->qty_planned,
                'status' => $row->status,
                'planned_start' => $row->planned_start?->format('Y-m-d') ?? '—',
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

    protected function buildActionHtml(ProductionOrder $order, User $actor): string
    {
        $html = '<div class="btn-list d-flex flex-wrap gap-1">';
        if ($actor->can('view', $order)) {
            $html .= PdfDownloadLink::button(route('admin.production.work-orders.pdf', $order));
            $html .= '<a href="'.e(route('admin.production.work-orders.edit', $order)).'" class="btn btn-sm btn-primary btn-wave">Edit</a>';
        }
        $html .= '</div>';

        return $html;
    }

    public function create(): View
    {
        $this->authorize('create', ProductionOrder::class);

        return view('admin.production.work-orders-create', [
            'items' => Item::query()->where('is_active', true)->orderBy('sku')->get(['id', 'sku', 'name']),
            'warehouses' => \App\Models\Warehouse::query()->where('is_active', true)->orderBy('code')->get(['id', 'code', 'name']),
        ]);
    }

    public function store(StoreProductionOrderRequest $request): JsonResponse
    {
        $user = $request->user();
        if ($user === null) {
            abort(403);
        }

        try {
            $v = $request->validated();
            ProductionOrder::query()->create([
                'wo_number' => $this->nextDocumentCode('production_orders', 'WO-'),
                'item_id' => $v['item_id'],
                'bom_id' => $v['bom_id'] ?? null,
                'warehouse_id' => $v['warehouse_id'] ?? null,
                'qty_planned' => $v['qty_planned'],
                'status' => 'planned',
                'planned_start' => $v['planned_start'] ?? null,
                'planned_end' => $v['planned_end'] ?? null,
                'created_by' => $user->id,
                'notes' => $v['notes'] ?? null,
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Work order created successfully.',
            ], 201);
        } catch (Throwable $e) {
            Log::error('ProductionOrderController@store failed', ['message' => $e->getMessage()]);

            return response()->json([
                'status' => false,
                'message' => 'Could not create work order.',
            ], 500);
        }
    }

    public function edit(ProductionOrder $productionOrder): View
    {
        $this->authorize('update', $productionOrder);

        return view('admin.production.work-orders-edit', ['productionOrder' => $productionOrder]);
    }

    public function update(UpdateProductionOrderRequest $request, ProductionOrder $productionOrder): JsonResponse
    {
        try {
            $before = $productionOrder->status;
            $newStatus = (string) $request->validated('status');
            $payload = $request->safe()->only(['status', 'notes', 'actual_qty']);
            DB::transaction(function () use ($request, $productionOrder, $before, $newStatus, $payload): void {
                if ($newStatus === 'in_progress' && $before !== 'in_progress') {
                    $this->productionRelease->releaseMaterials($productionOrder);
                }
                if ($newStatus === 'completed' && $before !== 'completed') {
                    $user = $request->user();
                    if ($productionOrder->warehouse_id === null) {
                        throw new \InvalidArgumentException('Warehouse is required before completing a work order.');
                    }
                    $bom = $productionOrder->bom_id !== null
                        ? BillOfMaterial::query()->findOrFail((int) $productionOrder->bom_id)
                        : BillOfMaterial::activeForItem((int) $productionOrder->item_id);
                    if ($bom === null) {
                        throw new \InvalidArgumentException('No active BOM found for this product.');
                    }
                    $outQty = $request->validated('actual_qty') ?? $productionOrder->qty_planned;
                    $this->stockService->applyProductionCompletion(
                        $productionOrder,
                        $bom,
                        (string) $outQty,
                        $user?->id
                    );
                }
                $productionOrder->update($payload);
            });

            $productionOrder->refresh();
            if ($before !== $newStatus) {
                if ($newStatus === 'in_progress') {
                    $this->whatsapp->notifyProductionStarted($productionOrder);
                    $this->pdfGenerator->queue(PdfDocumentType::ProductionOrder, $productionOrder, $request->user()?->id);
                }
                if ($newStatus === 'completed') {
                    $this->whatsapp->notifyProductionCompleted($productionOrder);
                }
                $this->auditLog->record(
                    'production.status_changed',
                    'Work order '.$productionOrder->wo_number.' status: '.$before.' → '.$newStatus,
                    $productionOrder,
                    $request->user()
                );
            }

            return response()->json([
                'status' => true,
                'message' => 'Work order updated successfully.',
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (Throwable $e) {
            Log::error('ProductionOrderController@update failed', ['message' => $e->getMessage()]);

            return response()->json([
                'status' => false,
                'message' => 'Could not update work order.',
            ], 500);
        }
    }
}
