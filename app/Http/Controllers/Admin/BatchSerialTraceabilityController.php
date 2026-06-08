<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\Warehouse;
use App\Services\BatchSerialTraceabilityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Batch/serial traceability dashboard (FEFO, expiry, movement history).
 */
class BatchSerialTraceabilityController extends Controller
{
    public function __construct(
        protected BatchSerialTraceabilityService $traceability
    ) {}

    public function index(Request $request): View
    {
        $user = $request->user();
        if ($user === null || ! $user->can('reports.inventory')) {
            abort(403);
        }

        return view('admin.inventory.batch-traceability', [
            'summary' => $this->traceability->summary(),
            'warehouses' => Warehouse::query()->where('is_active', true)->orderBy('code')->get(['id', 'code', 'name']),
            'items' => Item::query()
                ->where(function ($q): void {
                    $q->where('is_batch_tracked', true)->orWhere('is_serial_tracked', true);
                })
                ->where('is_active', true)
                ->orderBy('sku')
                ->get(['id', 'sku', 'name', 'is_batch_tracked', 'is_serial_tracked']),
            'expiryWarnDays' => (int) config('inventory.expiry_warn_days', 30),
        ]);
    }

    public function fefoData(Request $request): JsonResponse
    {
        $this->authorizeInventoryReports($request);

        return response()->json($this->traceability->fefoDataTable($request, $this->filters($request)));
    }

    public function expiryData(Request $request): JsonResponse
    {
        $this->authorizeInventoryReports($request);

        return response()->json($this->traceability->expiryDataTable($request, $this->filters($request)));
    }

    public function historyData(Request $request): JsonResponse
    {
        $this->authorizeInventoryReports($request);

        return response()->json($this->traceability->historyDataTable($request, $this->historyFilters($request)));
    }

    public function exportFefoCsv(Request $request): StreamedResponse
    {
        $this->authorizeInventoryReports($request);

        return $this->traceability->downloadFefoCsv($this->filters($request));
    }

    public function exportHistoryCsv(Request $request): StreamedResponse
    {
        $this->authorizeInventoryReports($request);

        return $this->traceability->downloadHistoryCsv($this->historyFilters($request));
    }

    protected function authorizeInventoryReports(Request $request): void
    {
        $user = $request->user();
        if ($user === null || ! $user->can('reports.inventory')) {
            abort(403);
        }
    }

    /**
     * @return array{warehouse_id?: int|null, item_id?: int|null, tracking?: string|null}
     */
    protected function filters(Request $request): array
    {
        return [
            'warehouse_id' => $request->filled('warehouse_id') ? (int) $request->input('warehouse_id') : null,
            'item_id' => $request->filled('item_id') ? (int) $request->input('item_id') : null,
            'tracking' => $request->input('tracking') ?: null,
        ];
    }

    /**
     * @return array{warehouse_id?: int|null, item_id?: int|null, date_from?: string|null, date_to?: string|null, batch_no?: string|null, serial_no?: string|null}
     */
    protected function historyFilters(Request $request): array
    {
        return array_merge($this->filters($request), [
            'date_from' => $request->input('date_from'),
            'date_to' => $request->input('date_to'),
            'batch_no' => $request->input('batch_no'),
            'serial_no' => $request->input('serial_no'),
        ]);
    }
}
