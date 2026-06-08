<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

/**
 * Shared server-side DataTables query for admin ERP listings.
 */
final class ErpDataTable
{
    /**
     * @param  Builder<Model>  $query  Base query with select/search columns; search not yet applied.
     * @param  callable(Builder<Model>, string): void  $applySearch  Apply OR-like search for the given term.
     * @param  list<string>  $orderableColumns  Column keys matching DataTables `columns[].data`.
     * @return array{draw: int, recordsTotal: int, recordsFiltered: int, rows: \Illuminate\Database\Eloquent\Collection<int, Model>}
     */
    public static function run(
        Builder $query,
        Request $request,
        callable $applySearch,
        array $orderableColumns,
        string $defaultOrderColumn = 'id',
        string $defaultOrderDir = 'desc',
        ?Builder $recordsTotalQuery = null,
    ): array {
        $draw = (int) $request->input('draw', 1);
        $search = $request->input('search.value');
        $start = (int) $request->input('start', 0);
        $length = (int) $request->input('length', 25);

        $filteredQuery = clone $query;
        if (is_string($search) && $search !== '') {
            $filteredQuery->where(function ($q) use ($search, $applySearch): void {
                $applySearch($q, $search);
            });
        }

        $model = $query->getModel();
        $totalBase = $recordsTotalQuery ?? $model->newQuery();
        $recordsTotal = (clone $totalBase)->toBase()->count();
        $recordsFiltered = (clone $filteredQuery)->toBase()->count();

        $columns = $request->input('columns', []);
        $orders = $request->input('order', []);
        if (isset($orders[0]['column'])) {
            $colIdx = (int) $orders[0]['column'];
            $dir = ($orders[0]['dir'] ?? 'desc') === 'asc' ? 'asc' : 'desc';
            $colData = $columns[$colIdx]['data'] ?? $defaultOrderColumn;
            if (in_array($colData, $orderableColumns, true)) {
                $filteredQuery->orderBy($colData, $dir);
            } else {
                $filteredQuery->orderBy($defaultOrderColumn, $defaultOrderDir);
            }
        } else {
            $filteredQuery->orderBy($defaultOrderColumn, $defaultOrderDir);
        }

        if ($length === -1) {
            $length = max(1, $recordsFiltered);
        }

        /** @var \Illuminate\Database\Eloquent\Collection<int, Model> $rows */
        $rows = $filteredQuery->skip($start)->take($length)->get();

        return [
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'rows' => $rows,
        ];
    }
}
