<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreStockReconciliationRequest;
use App\Models\StockReconciliation;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\StockReconciliationService;
use App\Support\ErpDataTable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Throwable;

class StockReconciliationController extends Controller
{
    public function __construct(
        protected StockReconciliationService $service
    ) {}

    public function index(): View
    {
        $this->authorize('viewAny', StockReconciliation::class);

        return view('admin.inventory.stock-reconciliation-index', [
            'warehouses' => Warehouse::query()->where('is_active', true)->orderBy('code')->get(['id', 'code', 'name']),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $this->authorize('viewAny', StockReconciliation::class);

        $query = StockReconciliation::query()
            ->select(['id', 'reconciliation_number', 'warehouse_id', 'period_year', 'period_month', 'status', 'created_at'])
            ->with('warehouse:id,code,name');

        $payload = ErpDataTable::run(
            $query,
            $request,
            function ($q, string $term): void {
                $q->where('reconciliation_number', 'like', '%'.$term.'%')
                    ->orWhere('status', 'like', '%'.$term.'%');
            },
            ['id', 'reconciliation_number', 'status', 'created_at'],
        );

        $actor = $request->user();
        if ($actor === null) {
            abort(403);
        }

        $data = $payload['rows']->map(function (StockReconciliation $row) use ($actor) {
            return [
                'number' => $row->reconciliation_number,
                'warehouse' => $row->warehouse?->code ?? '—',
                'period' => $row->period_year.'-'.str_pad((string) $row->period_month, 2, '0', STR_PAD_LEFT),
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

    protected function buildActionHtml(StockReconciliation $recon, User $actor): string
    {
        $html = '<div class="btn-list d-flex flex-wrap gap-1">';
        $html .= '<a href="'.e(route('admin.inventory.stock-reconciliations.show', $recon)).'" class="btn btn-sm btn-outline-primary btn-wave">View</a>';
        if ($actor->can('post', $recon)) {
            $html .= '<button type="button" class="btn btn-sm btn-success btn-wave js-sr-post" data-url="'
                .e(route('admin.inventory.stock-reconciliations.post', $recon)).'">Post</button>';
        }
        $html .= '</div>';

        return $html;
    }

    public function store(StoreStockReconciliationRequest $request): JsonResponse
    {
        $user = $request->user();
        if ($user === null) {
            abort(403);
        }

        try {
            $v = $request->validated();
            $recon = $this->service->createDraft(
                (int) $v['warehouse_id'],
                (int) $v['period_year'],
                (int) $v['period_month'],
                $user
            );

            return response()->json([
                'status' => true,
                'message' => 'Reconciliation draft created.',
                'redirect' => route('admin.inventory.stock-reconciliations.show', $recon),
            ], 201);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 422);
        } catch (Throwable $e) {
            Log::error('StockReconciliationController@store failed', ['message' => $e->getMessage()]);

            return response()->json(['status' => false, 'message' => 'Could not create reconciliation.'], 500);
        }
    }

    public function show(StockReconciliation $stockReconciliation): View
    {
        $this->authorize('view', $stockReconciliation);
        $stockReconciliation->load(['lines.item', 'warehouse']);

        return view('admin.inventory.stock-reconciliation-show', [
            'reconciliation' => $stockReconciliation,
        ]);
    }

    public function updateCounts(Request $request, StockReconciliation $stockReconciliation): JsonResponse
    {
        $this->authorize('update', $stockReconciliation);

        $data = $request->validate([
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.id' => ['required', 'integer'],
            'lines.*.physical_qty' => ['required', 'numeric', 'min:0'],
            'lines.*.reason' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $this->service->updateCounts($stockReconciliation, $data['lines']);

            return response()->json(['status' => true, 'message' => 'Physical counts saved.']);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function post(StockReconciliation $stockReconciliation): JsonResponse
    {
        $this->authorize('post', $stockReconciliation);
        $user = request()->user();
        if ($user === null) {
            abort(403);
        }

        try {
            $this->service->post($stockReconciliation, $user);

            return response()->json(['status' => true, 'message' => 'Reconciliation posted. Stock adjusted.']);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 422);
        }
    }
}
