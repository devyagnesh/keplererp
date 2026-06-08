<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreGrnReturnRequest;
use App\Models\GoodsReceipt;
use App\Models\GrnReturn;
use App\Services\GrnReturnService;
use App\Support\ErpDataTable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use InvalidArgumentException;
use Throwable;

class GrnReturnController extends Controller
{
    public function __construct(protected GrnReturnService $grnReturns) {}

    public function index(): View
    {
        $this->authorize('viewAny', GrnReturn::class);

        $grns = GoodsReceipt::query()
            ->where('status', 'posted')
            ->orderByDesc('id')
            ->limit(50)
            ->with(['lines.item:id,sku,name', 'vendor:id,name'])
            ->get(['id', 'grn_number', 'vendor_id', 'warehouse_id']);

        return view('admin.purchase.grn-returns-index', [
            'grns' => $grns,
            'items' => \App\Models\Item::query()->where('is_active', true)->orderBy('sku')->get(['id', 'sku', 'name']),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $this->authorize('viewAny', GrnReturn::class);

        $query = GrnReturn::query()
            ->select(['id', 'return_number', 'goods_receipt_id', 'vendor_id', 'status', 'posted_at', 'created_at'])
            ->with(['vendor:id,name', 'goodsReceipt:id,grn_number']);

        $payload = ErpDataTable::run(
            $query,
            $request,
            fn ($q, string $term) => $q->where('return_number', 'like', '%'.$term.'%'),
            ['id', 'return_number', 'status', 'posted_at', 'created_at'],
        );

        $data = $payload['rows']->map(fn (GrnReturn $row) => [
            'return_number' => $row->return_number,
            'grn' => $row->goodsReceipt?->grn_number ?? '—',
            'vendor' => $row->vendor?->name ?? '—',
            'status' => $row->status,
            'posted_at' => $row->posted_at?->format('Y-m-d H:i') ?? '—',
        ])->values()->all();

        return response()->json([
            'draw' => $payload['draw'],
            'recordsTotal' => $payload['recordsTotal'],
            'recordsFiltered' => $payload['recordsFiltered'],
            'data' => $data,
        ]);
    }

    public function store(StoreGrnReturnRequest $request): JsonResponse
    {
        $this->authorize('create', GrnReturn::class);
        $user = $request->user();
        if ($user === null) {
            abort(403);
        }

        try {
            $v = $request->validated();
            $grn = GoodsReceipt::query()->findOrFail((int) $v['goods_receipt_id']);
            $this->grnReturns->createAndPost(
                $grn,
                $v['lines'],
                $v['reason'] ?? null,
                (string) $v['debit_amount'],
                $user
            );

            return response()->json([
                'status' => true,
                'message' => 'GRN return posted and stock adjusted.',
            ], 201);
        } catch (InvalidArgumentException $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 422);
        } catch (Throwable $e) {
            Log::error('GrnReturnController@store failed', ['message' => $e->getMessage()]);

            return response()->json(['status' => false, 'message' => 'Could not post GRN return.'], 500);
        }
    }
}
