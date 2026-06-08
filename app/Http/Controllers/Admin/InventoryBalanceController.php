<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\InventoryBalance;
use App\Support\ErpDataTable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InventoryBalanceController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', InventoryBalance::class);

        return view('admin.inventory.balances-index');
    }

    public function data(Request $request): JsonResponse
    {
        $this->authorize('viewAny', InventoryBalance::class);

        $query = InventoryBalance::query()
            ->select([
                'inventory_balances.id',
                'inventory_balances.warehouse_id',
                'inventory_balances.item_id',
                'inventory_balances.quantity',
                'items.sku as item_sku',
                'items.name as item_name',
                'warehouses.code as warehouse_code',
                'warehouses.name as warehouse_name',
            ])
            ->join('items', 'items.id', '=', 'inventory_balances.item_id')
            ->join('warehouses', 'warehouses.id', '=', 'inventory_balances.warehouse_id');

        $payload = ErpDataTable::run(
            $query,
            $request,
            function ($q, string $term): void {
                $q->where('items.sku', 'like', '%'.$term.'%')
                    ->orWhere('items.name', 'like', '%'.$term.'%')
                    ->orWhere('warehouses.code', 'like', '%'.$term.'%')
                    ->orWhere('warehouses.name', 'like', '%'.$term.'%');
            },
            ['id', 'warehouse_code', 'item_sku', 'item_name', 'quantity'],
            'id',
            'desc'
        );

        $data = $payload['rows']->map(function ($row) {
            return [
                'warehouse_code' => $row->warehouse_code,
                'warehouse_name' => $row->warehouse_name,
                'item_name' => Item::formatLabel($row->item_name, $row->item_sku),
                'quantity' => (string) $row->quantity,
            ];
        })->values()->all();

        return response()->json([
            'draw' => $payload['draw'],
            'recordsTotal' => $payload['recordsTotal'],
            'recordsFiltered' => $payload['recordsFiltered'],
            'data' => $data,
        ]);
    }
}
