<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PdfDocumentType;
use App\Http\Controllers\Admin\Concerns\IssuesDocumentNumbers;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreGoodsReceiptRequest;
use App\Models\GoodsReceipt;
use App\Models\Item;
use App\Models\PurchaseOrder;
use App\Models\User;
use App\Models\Vendor;
use App\Models\Warehouse;
use App\Services\GoodsReceiptPostService;
use App\Services\Pdf\PdfGeneratorService;
use App\Support\ErpDataTable;
use App\Support\PdfDownloadLink;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use InvalidArgumentException;
use Throwable;

class GoodsReceiptController extends Controller
{
    use IssuesDocumentNumbers;

    public function __construct(
        protected GoodsReceiptPostService $grnPost,
        protected PdfGeneratorService $pdfGenerator
    ) {}

    public function index(): View
    {
        $this->authorize('viewAny', GoodsReceipt::class);

        return view('admin.purchase.grns-index');
    }

    public function data(Request $request): JsonResponse
    {
        $this->authorize('viewAny', GoodsReceipt::class);

        $query = GoodsReceipt::query()
            ->select(['goods_receipts.id', 'grn_number', 'vendor_id', 'warehouse_id', 'received_at', 'status', 'created_at'])
            ->with(['vendor:id,name', 'warehouse:id,code']);

        $payload = ErpDataTable::run(
            $query,
            $request,
            function ($q, string $term): void {
                $q->where('grn_number', 'like', '%'.$term.'%')
                    ->orWhereHas('vendor', function ($vq) use ($term): void {
                        $vq->where('name', 'like', '%'.$term.'%');
                    });
            },
            ['id', 'grn_number', 'received_at', 'status', 'created_at'],
        );

        $actor = $request->user();
        if ($actor === null) {
            abort(403);
        }

        $data = $payload['rows']->map(function (GoodsReceipt $row) use ($actor) {
            return [
                'grn_number' => $row->grn_number,
                'vendor' => $row->vendor?->name ?? '—',
                'warehouse' => $row->warehouse?->code ?? '—',
                'status' => $row->status,
                'received_at' => $row->received_at?->format('Y-m-d H:i'),
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

    protected function buildActionHtml(GoodsReceipt $grn, User $actor): string
    {
        $html = '<div class="btn-list d-flex flex-wrap gap-1">';
        if ($actor->can('view', $grn) && $grn->status === 'posted') {
            $html .= PdfDownloadLink::button(route('admin.purchase.grns.pdf', $grn));
        }
        if ($actor->can('post', $grn) && $grn->status === 'draft') {
            $html .= '<button type="button" class="btn btn-sm btn-success btn-wave js-grn-post" data-url="'
                .e(route('admin.purchase.grns.post', $grn)).'">Post</button>';
        }
        $html .= '</div>';

        return $html;
    }

    /**
     * Download or generate GRN PDF.
     */
    public function downloadPdf(Request $request, GoodsReceipt $goodsReceipt): Response
    {
        $this->authorize('view', $goodsReceipt);
        if ($goodsReceipt->status !== 'posted') {
            abort(404);
        }

        return $this->pdfGenerator->downloadOrGenerate(
            PdfDocumentType::Grn,
            $goodsReceipt,
            $request->user()?->id
        );
    }

    public function create(): View
    {
        $this->authorize('create', GoodsReceipt::class);

        return view('admin.purchase.grns-create', [
            'vendors' => Vendor::query()->orderBy('name')->get(['id', 'name']),
            'warehouses' => Warehouse::query()->where('is_active', true)->orderBy('code')->get(['id', 'code', 'name']),
            'purchaseOrders' => PurchaseOrder::query()
                ->whereIn('status', ['approved', 'sent'])
                ->orderByDesc('id')
                ->get(['id', 'po_number', 'vendor_id', 'warehouse_id']),
            'items' => Item::query()->where('is_active', true)->orderBy('sku')->get(['id', 'sku', 'name']),
        ]);
    }

    public function store(StoreGoodsReceiptRequest $request): JsonResponse
    {
        $user = $request->user();
        if ($user === null) {
            abort(403);
        }

        try {
            $v = $request->validated();
            $po = PurchaseOrder::query()->with('lines')->findOrFail((int) $v['purchase_order_id']);
            if (! in_array($po->status, ['approved', 'sent'], true)) {
                throw new InvalidArgumentException('Purchase order must be approved (or marked sent) before GRN.');
            }
            if ((int) $po->vendor_id !== (int) $v['vendor_id']) {
                throw new InvalidArgumentException('Vendor must match the linked purchase order.');
            }
            if ($po->warehouse_id !== null && (int) $po->warehouse_id !== (int) $v['warehouse_id']) {
                throw new InvalidArgumentException('Warehouse must match the purchase order warehouse.');
            }

            $lines = [];
            foreach ($v['lines'] as $line) {
                $accepted = isset($line['accepted_qty']) && $line['accepted_qty'] !== ''
                    ? (string) $line['accepted_qty']
                    : (string) $line['quantity'];
                $rejected = isset($line['rejected_qty']) && $line['rejected_qty'] !== ''
                    ? (string) $line['rejected_qty']
                    : '0.0000';
                $lines[] = [
                    'item_id' => (int) $line['item_id'],
                    'quantity' => (string) $line['quantity'],
                    'accepted_qty' => $accepted,
                    'rejected_qty' => $rejected,
                    'qc_status' => $line['qc_status'] ?? null,
                    'qc_remarks' => $line['qc_remarks'] ?? null,
                    'batch_no' => isset($line['batch_no']) && $line['batch_no'] !== ''
                        ? (string) $line['batch_no']
                        : null,
                    'serial_no' => isset($line['serial_no']) && $line['serial_no'] !== ''
                        ? (string) $line['serial_no']
                        : null,
                    'expiry_date' => isset($line['expiry_date']) && $line['expiry_date'] !== ''
                        ? (string) $line['expiry_date']
                        : null,
                ];
            }

            $qcPhotoPath = null;
            $qcPhoto = $request->file('qc_photo');
            if ($qcPhoto instanceof UploadedFile) {
                $qcPhotoPath = $qcPhoto->store('grn/qc-photos', 'local');
            }

            $postedGrn = null;
            DB::transaction(function () use ($v, $user, $lines, $po, $qcPhotoPath, &$postedGrn): void {
                $grn = GoodsReceipt::query()->create([
                    'grn_number' => $this->nextDocumentCode('goods_receipts', 'GRN-'),
                    'purchase_order_id' => $po->id,
                    'vendor_id' => $v['vendor_id'],
                    'warehouse_id' => $v['warehouse_id'],
                    'received_at' => $v['received_at'],
                    'created_by' => $user->id,
                    'notes' => $v['notes'] ?? null,
                    'qc_officer_name' => $v['qc_officer_name'] ?? null,
                    'qc_photo_path' => $qcPhotoPath,
                    'status' => 'draft',
                ]);
                foreach ($lines as $line) {
                    $grn->lines()->create([
                        'item_id' => $line['item_id'],
                        'quantity' => $line['quantity'],
                        'accepted_qty' => $line['accepted_qty'],
                        'rejected_qty' => $line['rejected_qty'],
                        'qc_status' => $line['qc_status'],
                        'qc_remarks' => $line['qc_remarks'],
                        'batch_no' => $line['batch_no'],
                        'serial_no' => $line['serial_no'],
                        'expiry_date' => $line['expiry_date'],
                    ]);
                }
                $postedGrn = $grn;
            });

            return response()->json([
                'status' => true,
                'message' => 'Goods receipt saved as draft. Post from the GRN list when QC is complete.',
            ], 201);
        } catch (InvalidArgumentException $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (Throwable $e) {
            Log::error('GoodsReceiptController@store failed', ['message' => $e->getMessage()]);

            return response()->json([
                'status' => false,
                'message' => 'Could not create goods receipt.',
            ], 500);
        }
    }

    public function post(Request $request, GoodsReceipt $goodsReceipt): JsonResponse
    {
        $this->authorize('post', $goodsReceipt);
        $user = $request->user();
        if ($user === null) {
            abort(403);
        }

        try {
            $this->grnPost->post($goodsReceipt, $user);

            return response()->json([
                'status' => true,
                'message' => 'Goods receipt posted to inventory and accounts.',
            ]);
        } catch (InvalidArgumentException $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 422);
        } catch (Throwable $e) {
            Log::error('GoodsReceiptController@post failed', ['message' => $e->getMessage()]);

            return response()->json(['status' => false, 'message' => 'Could not post goods receipt.'], 500);
        }
    }
}
