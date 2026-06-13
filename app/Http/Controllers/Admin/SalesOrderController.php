<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PdfDocumentType;
use App\Http\Controllers\Admin\Concerns\IssuesDocumentNumbers;
use App\Http\Controllers\Controller;
use App\Http\Requests\DispatchSalesOrderRequest;
use App\Http\Requests\MarkSalesOrderProcessingRequest;
use App\Http\Requests\StoreSalesOrderRequest;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Item;
use App\Models\SalesOrder;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\AuditLogService;
use App\Services\BatchSerialInventoryService;
use App\Services\DispatchChallanService;
use App\Services\GstCalculationService;
use App\Services\InventoryStockService;
use App\Services\Pdf\PdfGeneratorService;
use App\Services\PriceListService;
use App\Services\ProductionSuggestService;
use App\Services\Sales\SalesPickListService;
use App\Services\SalesInvoiceService;
use App\Services\SalesOrderProcessingService;
use App\Support\ErpDataTable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Throwable;

class SalesOrderController extends Controller
{
    use IssuesDocumentNumbers;

    public function __construct(
        protected GstCalculationService $gst,
        protected InventoryStockService $stockService,
        protected DispatchChallanService $dispatchChallans,
        protected AuditLogService $auditLog,
        protected SalesOrderProcessingService $orderProcessing,
        protected PriceListService $priceLists,
        protected ProductionSuggestService $productionSuggest,
        protected BatchSerialInventoryService $batchSerial,
        protected SalesPickListService $pickList,
        protected PdfGeneratorService $pdfGenerator
    ) {}

    public function index(): View
    {
        $this->authorize('viewAny', SalesOrder::class);

        return view('admin.sales.orders-index');
    }

    public function data(Request $request): JsonResponse
    {
        $this->authorize('viewAny', SalesOrder::class);

        $query = SalesOrder::query()
            ->select(['sales_orders.id', 'order_number', 'customer_id', 'order_date', 'status', 'total_amount', 'dispatched_at', 'created_at'])
            ->with(['customer:id,name', 'postedInvoice:id,sales_order_id,invoice_number', 'dispatchChallan:id,sales_order_id,challan_number']);

        $payload = ErpDataTable::run(
            $query,
            $request,
            function ($q, string $term): void {
                $q->where('order_number', 'like', '%'.$term.'%')
                    ->orWhereHas('customer', function ($cq) use ($term): void {
                        $cq->where('name', 'like', '%'.$term.'%');
                    });
            },
            ['id', 'order_number', 'order_date', 'status', 'total_amount', 'created_at'],
        );

        $actor = $request->user();
        if ($actor === null) {
            abort(403);
        }

        $data = $payload['rows']->map(function (SalesOrder $row) use ($actor) {
            return [
                'order_number' => $row->order_number,
                'customer' => $row->customer?->name ?? '—',
                'order_date' => $row->order_date?->format('Y-m-d'),
                'status' => $row->status,
                'total_amount' => (string) $row->total_amount,
                'dispatched_at' => $row->dispatched_at?->format('Y-m-d H:i') ?? '—',
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

    protected function buildActionHtml(SalesOrder $order, User $actor): string
    {
        $html = '<div class="btn-list d-flex flex-wrap gap-1">';
        if ($actor->can('process', $order)) {
            $html .= '<button type="button" class="btn btn-sm btn-warning btn-wave js-so-process" data-confirm="Move to pick &amp; pack?" data-url="'
                .e(route('admin.sales.orders.process', $order)).'">Pick &amp; pack</button>';
        }
        if ($actor->can('dispatch', $order) && in_array($order->status, ['processing'], true)) {
            $html .= '<button type="button" class="btn btn-sm btn-info btn-wave js-so-pick-list" data-url="'
                .e(route('admin.sales.orders.pick-list', $order)).'" data-confirm-url="'
                .e(route('admin.sales.orders.pick-list.confirm', $order)).'" data-pdf-url="'
                .e(route('admin.sales.orders.pick-list.pdf', $order)).'">Pick list</button>';
        }
        if ($actor->can('dispatch', $order)) {
            $html .= '<button type="button" class="btn btn-sm btn-success btn-wave js-so-dispatch" data-url="'
                .e(route('admin.sales.orders.dispatch', $order)).'" data-dispatch-data-url="'
                .e(route('admin.sales.orders.dispatch-data', $order)).'">Dispatch</button>';
        }
        if ($actor->can('create', SalesOrder::class) && $order->status === 'confirmed' && $order->warehouse_id !== null) {
            $html .= '<button type="button" class="btn btn-sm btn-outline-info btn-wave js-so-suggest-wo" data-confirm="Create work orders for low FG stock?" data-url="'
                .e(route('admin.sales.orders.suggest-production', $order)).'">Suggest WO</button>';
        }
        if ($actor->can('create', Invoice::class) && in_array($order->status, ['confirmed', 'dispatched', 'processing'], true)) {
            $html .= '<button type="button" class="btn btn-sm btn-outline-primary btn-wave js-so-invoice" data-confirm="Post invoice from this order?" data-url="'
                .e(route('admin.sales.orders.invoice', $order)).'">Invoice</button>';
        }
        if ($order->postedInvoice !== null && $actor->can('view', $order->postedInvoice)) {
            $html .= '<a href="'.e(route('admin.sales.invoices.pdf', $order->postedInvoice)).'" class="btn btn-sm btn-outline-secondary btn-wave" target="_blank">PDF</a>';
        }
        if ($order->dispatchChallan !== null && $actor->can('view', $order)) {
            $html .= '<a href="'.e(route('admin.sales.orders.challan.pdf', $order)).'" class="btn btn-sm btn-outline-secondary btn-wave" target="_blank">Challan</a>';
        }
        $html .= '</div>';

        return $html;
    }

    public function create(): View
    {
        $this->authorize('create', SalesOrder::class);

        return view('admin.sales.orders-create', [
            'customers' => Customer::query()->orderBy('name')->get(['id', 'name']),
            'items' => Item::query()->where('is_active', true)->orderBy('sku')->get(['id', 'sku', 'name']),
            'warehouses' => Warehouse::query()->where('is_active', true)->orderBy('code')->get(['id', 'code', 'name']),
        ]);
    }

    public function store(StoreSalesOrderRequest $request): JsonResponse
    {
        $user = $request->user();
        if ($user === null) {
            abort(403);
        }

        try {
            DB::transaction(function () use ($request, $user): void {
                $v = $request->validated();
                $customer = Customer::query()->findOrFail((int) $v['customer_id']);
                $company = Company::query()->orderBy('id')->firstOrFail();
                $computed = $this->computeSalesLinesAndHeader($v, $customer, $company);
                $so = SalesOrder::query()->create([
                    'order_number' => $this->nextDocumentCode('sales_orders', 'SO-'),
                    'customer_id' => $customer->id,
                    'warehouse_id' => (int) $v['warehouse_id'],
                    'customer_address_id' => $v['customer_address_id'] ?? null,
                    'order_date' => $v['order_date'],
                    'expected_dispatch' => $v['expected_dispatch'] ?? null,
                    'payment_terms_days' => (int) ($v['payment_terms_days'] ?? $customer->payment_terms_days ?? 30),
                    'status' => 'confirmed',
                    'created_by' => $user->id,
                    'notes' => $v['notes'] ?? null,
                    'subtotal' => $computed['subtotal'],
                    'discount_amount' => '0.00',
                    'taxable_amount' => $computed['taxable_amount'],
                    'cgst_amount' => $computed['cgst_amount'],
                    'sgst_amount' => $computed['sgst_amount'],
                    'igst_amount' => $computed['igst_amount'],
                    'total_amount' => $computed['total_amount'],
                ]);
                foreach ($computed['lines'] as $line) {
                    $so->lines()->create($line);
                }
                $so->refresh();
                $this->stockService->reserveForSalesOrder($so, $user->id);
            });

            return response()->json([
                'status' => true,
                'message' => 'Sales order created and stock reserved.',
            ], 201);
        } catch (Throwable $e) {
            Log::error('SalesOrderController@store failed', ['message' => $e->getMessage()]);

            return response()->json([
                'status' => false,
                'message' => 'Could not create sales order.',
            ], 500);
        }
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array{lines: list<array<string, mixed>>, subtotal: string, taxable_amount: string, cgst_amount: string, sgst_amount: string, igst_amount: string, total_amount: string}
     */
    protected function computeSalesLinesAndHeader(array $validated, Customer $customer, Company $company): array
    {
        $subtotal = '0.00';
        $cgstTot = '0.00';
        $sgstTot = '0.00';
        $igstTot = '0.00';
        $total = '0.00';
        $linesOut = [];

        foreach ($validated['lines'] as $line) {
            $item = Item::query()->findOrFail((int) $line['item_id']);
            $qty = (string) $line['quantity'];
            $unitPrice = isset($line['unit_price']) && $line['unit_price'] !== ''
                ? (string) $line['unit_price']
                : $this->priceLists->unitPriceForCustomer($customer, $item, '0');
            $taxable = $this->gst->lineTaxable($qty, $unitPrice);
            $split = $this->gst->splitLineTax($taxable, (string) $item->gst_rate, $customer->state_code, $company->state_code);
            $lineTotal = bcadd(bcadd(bcadd($split['taxable'], $split['cgst'], 2), $split['sgst'], 2), $split['igst'], 2);
            $subtotal = bcadd($subtotal, $split['taxable'], 2);
            $cgstTot = bcadd($cgstTot, $split['cgst'], 2);
            $sgstTot = bcadd($sgstTot, $split['sgst'], 2);
            $igstTot = bcadd($igstTot, $split['igst'], 2);
            $total = bcadd($total, $lineTotal, 2);
            $linesOut[] = [
                'item_id' => $item->id,
                'quantity' => $qty,
                'unit_price' => $unitPrice,
                'gst_rate' => (string) $item->gst_rate,
                'taxable_value' => $split['taxable'],
                'cgst' => $split['cgst'],
                'sgst' => $split['sgst'],
                'igst' => $split['igst'],
                'line_total' => $lineTotal,
            ];
        }

        return [
            'lines' => $linesOut,
            'subtotal' => $subtotal,
            'taxable_amount' => $subtotal,
            'cgst_amount' => $cgstTot,
            'sgst_amount' => $sgstTot,
            'igst_amount' => $igstTot,
            'total_amount' => $total,
        ];
    }

    public function suggestProduction(SalesOrder $salesOrder): JsonResponse
    {
        $this->authorize('create', SalesOrder::class);
        $user = request()->user();
        if ($user === null) {
            abort(403);
        }

        try {
            $orders = $this->productionSuggest->createFromSalesOrder($salesOrder, $user);
            $nums = collect($orders)->pluck('wo_number')->implode(', ');

            return response()->json([
                'status' => true,
                'message' => 'Created work order(s): '.$nums,
            ], 201);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function process(MarkSalesOrderProcessingRequest $request, SalesOrder $salesOrder): JsonResponse
    {
        $this->authorize('process', $salesOrder);

        try {
            $v = $request->validated();
            $this->orderProcessing->markProcessing(
                $salesOrder,
                $v['courier_name'] ?? null,
                $v['tracking_number'] ?? null,
                $v['transporter_name'] ?? null
            );

            return response()->json([
                'status' => true,
                'message' => 'Order moved to processing (pick & pack).',
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function dispatchData(SalesOrder $salesOrder): JsonResponse
    {
        $this->authorize('dispatch', $salesOrder);
        $salesOrder->loadMissing(['lines.item']);
        $warehouseId = (int) $salesOrder->warehouse_id;

        $lines = $salesOrder->lines->map(function ($line) use ($warehouseId) {
            $item = $line->item;
            $flags = $item !== null ? $this->batchSerial->trackingForItem((int) $item->id) : [
                'is_batch_tracked' => false,
                'is_serial_tracked' => false,
            ];

            return [
                'id' => $line->id,
                'item_id' => $line->item_id,
                'item_label' => $item !== null ? $item->display_label : '—',
                'quantity' => (string) $line->quantity,
                'is_batch_tracked' => $flags['is_batch_tracked'],
                'is_serial_tracked' => $flags['is_serial_tracked'],
                'batches' => $flags['is_batch_tracked']
                    ? $this->batchSerial->availableBatches($warehouseId, (int) $line->item_id)
                    : [],
                'serials' => $flags['is_serial_tracked']
                    ? $this->batchSerial->availableSerials($warehouseId, (int) $line->item_id)
                    : [],
            ];
        })->values()->all();

        return response()->json([
            'status' => true,
            'data' => [
                'warehouse_id' => $warehouseId,
                'lines' => $lines,
            ],
        ]);
    }

    public function dispatch(DispatchSalesOrderRequest $request, SalesOrder $salesOrder): JsonResponse
    {
        $this->authorize('dispatch', $salesOrder);

        try {
            $user = $request->user();
            $allocations = collect($request->validated()['lines'] ?? [])->keyBy('line_id');
            DB::transaction(function () use ($salesOrder, $user, $allocations): void {
                foreach ($salesOrder->lines as $line) {
                    $alloc = $allocations->get($line->id) ?? $allocations->get((string) $line->id);
                    if (is_array($alloc)) {
                        $line->update([
                            'batch_no' => isset($alloc['batch_no']) && $alloc['batch_no'] !== ''
                                ? (string) $alloc['batch_no']
                                : null,
                            'serial_no' => isset($alloc['serial_no']) && $alloc['serial_no'] !== ''
                                ? (string) $alloc['serial_no']
                                : null,
                        ]);
                    }
                }
                $salesOrder->load(['lines.item']);
                $this->stockService->applySalesDispatch($salesOrder, $user?->id);
                $salesOrder->update([
                    'status' => 'dispatched',
                    'dispatched_at' => now(),
                ]);
                $this->dispatchChallans->createForSalesOrder($salesOrder->fresh(), $user?->id);
            });

            $this->auditLog->record(
                'sales_order.dispatched',
                'Dispatched sales order '.$salesOrder->order_number,
                $salesOrder->fresh(),
                $user
            );

            return response()->json([
                'status' => true,
                'message' => 'Order dispatched, challan created, and stock deducted.',
            ]);
        } catch (Throwable $e) {
            Log::error('SalesOrderController@dispatch failed', ['message' => $e->getMessage()]);

            return response()->json([
                'status' => false,
                'message' => $e->getMessage() ?: 'Could not dispatch order.',
            ], 422);
        }
    }

    public function invoice(Request $request, SalesOrder $salesOrder): JsonResponse
    {
        $this->authorize('create', Invoice::class);
        $user = $request->user();
        if ($user === null) {
            abort(403);
        }

        try {
            $invoice = app(SalesInvoiceService::class)->createPostedFromSalesOrder(
                $salesOrder,
                $user,
                $this->nextDocumentCode('invoices', 'INV-')
            );

            return response()->json([
                'status' => true,
                'message' => 'Invoice '.$invoice->invoice_number.' posted successfully.',
            ]);
        } catch (Throwable $e) {
            Log::error('SalesOrderController@invoice failed', ['message' => $e->getMessage()]);

            return response()->json([
                'status' => false,
                'message' => $e->getMessage() ?: 'Could not post invoice.',
            ], 422);
        }
    }

    public function pickListData(SalesOrder $salesOrder): JsonResponse
    {
        $this->authorize('dispatch', $salesOrder);

        try {
            $data = $this->pickList->buildPickList($salesOrder);

            return response()->json([
                'status' => true,
                'data' => $data,
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function confirmPick(Request $request, SalesOrder $salesOrder): JsonResponse
    {
        $this->authorize('dispatch', $salesOrder);

        $scans = $request->input('scanned_codes', []);
        if (! is_array($scans)) {
            $scans = [];
        }

        try {
            $validation = $this->pickList->validateScans($salesOrder, $scans);
            if ($validation['matched'] < $validation['total']) {
                return response()->json([
                    'status' => false,
                    'message' => 'Barcode scan incomplete. Matched '.$validation['matched'].' of '.$validation['total'].'.',
                    'data' => $validation,
                ], 422);
            }

            $this->pickList->confirmPick($salesOrder, $request->input('packaging_notes'));
            $this->pdfGenerator->queue(PdfDocumentType::PickList, $salesOrder, $request->user()?->id);

            return response()->json([
                'status' => true,
                'message' => 'Pick confirmed. Pick list PDF queued.',
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function downloadPickListPdf(Request $request, SalesOrder $salesOrder): Response
    {
        $this->authorize('dispatch', $salesOrder);

        return $this->pdfGenerator->downloadOrGenerate(
            PdfDocumentType::PickList,
            $salesOrder,
            $request->user()?->id
        );
    }
}
