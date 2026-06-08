<?php

namespace App\Services;

use App\Models\Item;
use App\Models\StockLedger;
use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Batch/serial traceability: FEFO on-hand, expiry alerts, and movement history.
 */
class BatchSerialTraceabilityService
{
    /**
     * @return array{tracked_items: int, batches_on_hand: int, serials_on_hand: int, expired_batches: int, expiring_soon: int}
     */
    public function summary(): array
    {
        $trackedItems = Item::query()
            ->where(function ($q): void {
                $q->where('is_batch_tracked', true)->orWhere('is_serial_tracked', true);
            })
            ->where('is_active', true)
            ->count();

        $batchRows = $this->batchOnHandBaseQuery()->get();
        $serialRows = $this->serialOnHandBaseQuery()->get();
        $warnDays = (int) config('inventory.expiry_warn_days', 30);
        $warnUntil = now()->addDays($warnDays)->toDateString();
        $today = now()->toDateString();

        $expired = 0;
        $expiringSoon = 0;
        foreach ($batchRows as $row) {
            if ($row->expiry_date === null) {
                continue;
            }
            $exp = (string) $row->expiry_date;
            if ($exp < $today) {
                $expired++;
            } elseif ($exp <= $warnUntil) {
                $expiringSoon++;
            }
        }

        return [
            'tracked_items' => $trackedItems,
            'batches_on_hand' => $batchRows->count(),
            'serials_on_hand' => $serialRows->count(),
            'expired_batches' => $expired,
            'expiring_soon' => $expiringSoon,
        ];
    }

    /**
     * @param  array{warehouse_id?: int|null, item_id?: int|null, tracking?: string|null, expiry_status?: string|null}  $filters
     * @return array{draw: int, recordsTotal: int, recordsFiltered: int, data: list<array<string, mixed>>}
     */
    public function fefoDataTable(Request $request, array $filters): array
    {
        $rows = $this->mergeOnHandRows($filters);
        $search = $request->input('search.value');
        if (is_string($search) && $search !== '') {
            $term = mb_strtolower($search);
            $rows = $rows->filter(function (array $row) use ($term): bool {
                $hay = mb_strtolower(implode(' ', [
                    $row['warehouse_code'] ?? '',
                    $row['item_label'] ?? '',
                    $row['batch_no'] ?? '',
                    $row['serial_no'] ?? '',
                    $row['tracking'] ?? '',
                ]));

                return str_contains($hay, $term);
            });
        }

        $recordsFiltered = $rows->count();
        $recordsTotal = $this->mergeOnHandRows([])->count();

        $orderCol = (int) ($request->input('order.0.column', 6));
        $orderDir = ($request->input('order.0.dir', 'asc') === 'desc') ? 'desc' : 'asc';
        $columns = ['warehouse_code', 'item_label', 'tracking', 'batch_no', 'serial_no', 'on_hand', 'expiry_date', 'days_to_expiry', 'status'];
        $sortKey = $columns[$orderCol] ?? 'expiry_date';
        $rows = $orderDir === 'desc'
            ? $rows->sortByDesc($sortKey, SORT_NATURAL)
            : $rows->sortBy($sortKey, SORT_NATURAL);

        if ($sortKey === 'expiry_date') {
            $rows = $orderDir === 'desc'
                ? $rows->sortByDesc(fn (array $r) => $r['expiry_date'] ?? '9999-12-31')
                : $rows->sortBy(fn (array $r) => $r['expiry_date'] ?? '9999-12-31');
        }

        $start = (int) $request->input('start', 0);
        $length = (int) $request->input('length', 25);
        if ($length === -1) {
            $length = max(1, $recordsFiltered);
        }

        $page = $rows->slice($start, $length)->values();

        return [
            'draw' => (int) $request->input('draw', 1),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $page->all(),
        ];
    }

    /**
     * @param  array{warehouse_id?: int|null, item_id?: int|null}  $filters
     * @return array{draw: int, recordsTotal: int, recordsFiltered: int, data: list<array<string, mixed>>}
     */
    public function expiryDataTable(Request $request, array $filters): array
    {
        $filters['expiry_status'] = $request->input('expiry_status', 'all');
        $rows = $this->mergeOnHandRows($filters)->filter(fn (array $row): bool => $row['tracking'] === 'Batch' && $row['expiry_date'] !== null);

        $status = (string) ($filters['expiry_status'] ?? 'all');
        if ($status === 'expired') {
            $rows = $rows->filter(fn (array $r): bool => ($r['status'] ?? '') === 'Expired');
        } elseif ($status === 'expiring') {
            $rows = $rows->filter(fn (array $r): bool => ($r['status'] ?? '') === 'Expiring soon');
        } else {
            $rows = $rows->filter(fn (array $r): bool => in_array($r['status'] ?? '', ['Expired', 'Expiring soon'], true));
        }

        return $this->paginateCollection($request, $rows, $this->mergeOnHandRows(['tracking' => 'Batch'])->filter(fn ($r) => $r['expiry_date'] !== null)->count());
    }

    /**
     * @param  array{warehouse_id?: int|null, item_id?: int|null, date_from?: string|null, date_to?: string|null, batch_no?: string|null, serial_no?: string|null}  $filters
     */
    public function historyDataTable(Request $request, array $filters): array
    {
        $query = StockLedger::query()
            ->select([
                'stock_ledger.id',
                'stock_ledger.warehouse_id',
                'stock_ledger.item_id',
                'stock_ledger.batch_no',
                'stock_ledger.serial_no',
                'stock_ledger.expiry_date',
                'stock_ledger.transaction_type',
                'stock_ledger.qty_in',
                'stock_ledger.qty_out',
                'stock_ledger.balance_qty',
                'stock_ledger.reference_type',
                'stock_ledger.reference_id',
                'stock_ledger.created_at',
            ])
            ->with(['item:id,sku,name', 'warehouse:id,code,name'])
            ->where(function ($q): void {
                $q->where(function ($inner): void {
                    $inner->whereNotNull('batch_no')->where('batch_no', '!=', '');
                })->orWhere(function ($inner): void {
                    $inner->whereNotNull('serial_no')->where('serial_no', '!=', '');
                });
            });

        if (! empty($filters['warehouse_id'])) {
            $query->where('warehouse_id', (int) $filters['warehouse_id']);
        }
        if (! empty($filters['item_id'])) {
            $query->where('item_id', (int) $filters['item_id']);
        }
        if (! empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }
        if (! empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }
        if (! empty($filters['batch_no'])) {
            $query->where('batch_no', 'like', '%'.$filters['batch_no'].'%');
        }
        if (! empty($filters['serial_no'])) {
            $query->where('serial_no', 'like', '%'.$filters['serial_no'].'%');
        }

        $recordsTotal = (clone $query)->count();
        $search = $request->input('search.value');
        if (is_string($search) && $search !== '') {
            $term = '%'.$search.'%';
            $query->where(function ($q) use ($term): void {
                $q->where('batch_no', 'like', $term)
                    ->orWhere('serial_no', 'like', $term)
                    ->orWhere('transaction_type', 'like', $term)
                    ->orWhereHas('item', fn ($iq) => $iq->where('sku', 'like', $term)->orWhere('name', 'like', $term))
                    ->orWhereHas('warehouse', fn ($wq) => $wq->where('code', 'like', $term));
            });
        }

        $recordsFiltered = (clone $query)->count();
        $orderCol = (int) ($request->input('order.0.column', 0));
        $orderDir = ($request->input('order.0.dir', 'desc') === 'asc') ? 'asc' : 'desc';
        $orderMap = [
            'created_at' => 'created_at',
            'warehouse' => 'warehouse_id',
            'item_label' => 'item_id',
            'transaction_type' => 'transaction_type',
            'batch_no' => 'batch_no',
            'serial_no' => 'serial_no',
        ];
        $columns = array_keys($orderMap);
        $col = $columns[$orderCol] ?? 'created_at';
        $query->orderBy($orderMap[$col] ?? 'created_at', $orderDir);

        $start = (int) $request->input('start', 0);
        $length = (int) $request->input('length', 25);
        if ($length === -1) {
            $length = max(1, $recordsFiltered);
        }

        $rows = $query->skip($start)->take($length)->get();

        $data = $rows->map(function (StockLedger $row): array {
            $qty = $row->qty_in !== null ? (string) $row->qty_in : ('-'.(string) $row->qty_out);

            return [
                'created_at' => $row->created_at?->format('Y-m-d H:i') ?? '—',
                'warehouse' => ($row->warehouse?->code ?? '—').' — '.($row->warehouse?->name ?? ''),
                'item_label' => $row->item !== null
                    ? $row->item->display_label
                    : Item::formatLabel(null, null),
                'transaction_type' => $row->transaction_type,
                'batch_no' => $row->batch_no ?? '—',
                'serial_no' => $row->serial_no ?? '—',
                'expiry_date' => $row->expiry_date?->format('Y-m-d') ?? '—',
                'quantity' => $qty,
                'balance_qty' => (string) $row->balance_qty,
                'reference' => $this->formatReference($row->reference_type, $row->reference_id),
            ];
        })->values()->all();

        return [
            'draw' => (int) $request->input('draw', 1),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ];
    }

    /**
     * @param  array{warehouse_id?: int|null, item_id?: int|null}  $filters
     */
    public function downloadFefoCsv(array $filters): StreamedResponse
    {
        $rows = $this->mergeOnHandRows($filters)->sortBy(fn (array $r) => $r['expiry_date'] ?? '9999-12-31');

        return $this->streamCsv('batch-fefo-on-hand.csv', [
            'Warehouse', 'Item', 'Tracking', 'Batch', 'Serial', 'On hand', 'Expiry', 'Days', 'Status',
        ], $rows, fn (array $row) => [
            $row['warehouse_code'],
            $row['item_label'],
            $row['tracking'],
            $row['batch_no'],
            $row['serial_no'],
            $row['on_hand'],
            $row['expiry_date'] ?? '',
            $row['days_to_expiry'] ?? '',
            $row['status'],
        ]);
    }

    /**
     * @param  array{warehouse_id?: int|null, item_id?: int|null, date_from?: string|null, date_to?: string|null}  $filters
     */
    public function downloadHistoryCsv(array $filters): StreamedResponse
    {
        $fakeRequest = Request::create('/', 'POST', ['start' => 0, 'length' => -1, 'draw' => 1]);
        $payload = $this->historyDataTable($fakeRequest, $filters);

        return $this->streamCsv('batch-serial-history.csv', [
            'Date', 'Warehouse', 'Item', 'Transaction', 'Batch', 'Serial', 'Expiry', 'Qty', 'Balance', 'Reference',
        ], collect($payload['data']), fn (array $row) => [
            $row['created_at'],
            $row['warehouse'],
            $row['item_label'],
            $row['transaction_type'],
            $row['batch_no'],
            $row['serial_no'],
            $row['expiry_date'],
            $row['quantity'],
            $row['balance_qty'],
            $row['reference'],
        ]);
    }

    /**
     * @param  array{warehouse_id?: int|null, item_id?: int|null, tracking?: string|null, expiry_status?: string|null}  $filters
     */
    protected function mergeOnHandRows(array $filters): Collection
    {
        $batch = $this->batchOnHandBaseQuery();
        $serial = $this->serialOnHandBaseQuery();

        if (! empty($filters['warehouse_id'])) {
            $batch->where('sl.warehouse_id', (int) $filters['warehouse_id']);
            $serial->where('sl.warehouse_id', (int) $filters['warehouse_id']);
        }
        if (! empty($filters['item_id'])) {
            $batch->where('sl.item_id', (int) $filters['item_id']);
            $serial->where('sl.item_id', (int) $filters['item_id']);
        }

        $out = collect();
        if (($filters['tracking'] ?? '') !== 'serial') {
            foreach ($batch->get() as $row) {
                $out->push($this->mapOnHandRow($row, 'Batch'));
            }
        }
        if (($filters['tracking'] ?? '') !== 'batch') {
            foreach ($serial->get() as $row) {
                $out->push($this->mapOnHandRow($row, 'Serial'));
            }
        }

        return $out;
    }

    protected function batchOnHandBaseQuery(): Builder
    {
        return DB::table('stock_ledger as sl')
            ->join('items as i', 'i.id', '=', 'sl.item_id')
            ->join('warehouses as w', 'w.id', '=', 'sl.warehouse_id')
            ->select([
                'sl.warehouse_id',
                'sl.item_id',
                'i.sku',
                'i.name as item_name',
                'w.code as warehouse_code',
                'sl.batch_no',
                DB::raw('NULL as serial_no'),
                DB::raw('SUM(COALESCE(sl.qty_in, 0)) - SUM(COALESCE(sl.qty_out, 0)) as on_hand'),
                DB::raw('MIN(sl.expiry_date) as expiry_date'),
            ])
            ->where('i.is_batch_tracked', true)
            ->whereNotNull('sl.batch_no')
            ->where('sl.batch_no', '!=', '')
            ->groupBy('sl.warehouse_id', 'sl.item_id', 'i.sku', 'i.name', 'w.code', 'sl.batch_no')
            ->havingRaw('on_hand > 0');
    }

    protected function serialOnHandBaseQuery(): Builder
    {
        return DB::table('stock_ledger as sl')
            ->join('items as i', 'i.id', '=', 'sl.item_id')
            ->join('warehouses as w', 'w.id', '=', 'sl.warehouse_id')
            ->select([
                'sl.warehouse_id',
                'sl.item_id',
                'i.sku',
                'i.name as item_name',
                'w.code as warehouse_code',
                DB::raw('NULL as batch_no'),
                'sl.serial_no',
                DB::raw('SUM(COALESCE(sl.qty_in, 0)) - SUM(COALESCE(sl.qty_out, 0)) as on_hand'),
                DB::raw('MIN(sl.expiry_date) as expiry_date'),
            ])
            ->where('i.is_serial_tracked', true)
            ->whereNotNull('sl.serial_no')
            ->where('sl.serial_no', '!=', '')
            ->groupBy('sl.warehouse_id', 'sl.item_id', 'i.sku', 'i.name', 'w.code', 'sl.serial_no')
            ->havingRaw('on_hand > 0');
    }

    /**
     * @return array<string, mixed>
     */
    protected function mapOnHandRow(object $row, string $tracking): array
    {
        $onHand = bcadd((string) $row->on_hand, '0', 4);
        $expiry = $row->expiry_date !== null ? (string) $row->expiry_date : null;
        $days = null;
        $status = 'OK';
        if ($expiry !== null && $tracking === 'Batch') {
            $days = (int) now()->startOfDay()->diffInDays(Carbon::parse($expiry)->startOfDay(), false);
            if ($days < 0) {
                $status = 'Expired';
            } elseif ($days <= (int) config('inventory.expiry_warn_days', 30)) {
                $status = 'Expiring soon';
            }
        }

        return [
            'warehouse_code' => (string) $row->warehouse_code,
            'item_label' => Item::formatLabel((string) $row->item_name, (string) $row->sku),
            'tracking' => $tracking,
            'batch_no' => $row->batch_no !== null ? (string) $row->batch_no : '—',
            'serial_no' => $row->serial_no !== null ? (string) $row->serial_no : '—',
            'on_hand' => $onHand,
            'expiry_date' => $expiry,
            'days_to_expiry' => $days !== null ? (string) $days : '—',
            'status' => $status,
        ];
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $rows
     * @return array{draw: int, recordsTotal: int, recordsFiltered: int, data: list<array<string, mixed>>}
     */
    protected function paginateCollection(Request $request, Collection $rows, int $recordsTotal): array
    {
        $search = $request->input('search.value');
        if (is_string($search) && $search !== '') {
            $term = mb_strtolower($search);
            $rows = $rows->filter(function (array $row) use ($term): bool {
                return str_contains(mb_strtolower(json_encode($row) ?: ''), $term);
            });
        }

        $recordsFiltered = $rows->count();
        $start = (int) $request->input('start', 0);
        $length = (int) $request->input('length', 25);
        if ($length === -1) {
            $length = max(1, $recordsFiltered);
        }

        return [
            'draw' => (int) $request->input('draw', 1),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $rows->slice($start, $length)->values()->all(),
        ];
    }

    protected function formatReference(?string $type, ?int $id): string
    {
        if ($type === null || $id === null) {
            return '—';
        }

        $short = class_basename($type);

        return $short.' #'.$id;
    }

    /**
     * @param  list<string>  $headers
     * @param  Collection<int, mixed>  $rows
     * @param  callable(mixed): list<string>  $mapRow
     */
    protected function streamCsv(string $filename, array $headers, Collection $rows, callable $mapRow): StreamedResponse
    {
        return response()->streamDownload(function () use ($headers, $rows, $mapRow): void {
            $out = fopen('php://output', 'w');
            if ($out === false) {
                return;
            }
            fputcsv($out, $headers);
            foreach ($rows as $row) {
                fputcsv($out, $mapRow($row));
            }
            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
