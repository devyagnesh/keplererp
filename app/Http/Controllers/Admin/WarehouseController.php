<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreWarehouseRequest;
use App\Http\Requests\UpdateWarehouseRequest;
use App\Models\User;
use App\Models\Warehouse;
use App\Support\ErpDataTable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Throwable;

class WarehouseController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Warehouse::class);

        return view('admin.warehouses.index');
    }

    public function data(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Warehouse::class);

        $query = Warehouse::query()->select(['id', 'code', 'name', 'city', 'is_active', 'created_at']);

        $payload = ErpDataTable::run(
            $query,
            $request,
            function ($q, string $term): void {
                $q->where('code', 'like', '%'.$term.'%')
                    ->orWhere('name', 'like', '%'.$term.'%')
                    ->orWhere('city', 'like', '%'.$term.'%');
            },
            ['id', 'code', 'name', 'city', 'is_active', 'created_at'],
        );

        $actor = $request->user();
        if ($actor === null) {
            abort(403);
        }

        $data = $payload['rows']->map(function (Warehouse $row) use ($actor) {
            return [
                'code' => $row->code,
                'name' => $row->name,
                'city' => $row->city ?? '—',
                'is_active' => $row->is_active ? 'Yes' : 'No',
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

    protected function buildActionHtml(Warehouse $warehouse, User $actor): string
    {
        $html = '<div class="btn-list d-flex flex-wrap gap-1">';
        if ($actor->can('update', $warehouse)) {
            $html .= '<a href="'.e(route('admin.warehouses.edit', $warehouse)).'" class="btn btn-sm btn-primary btn-wave">Edit</a>';
        }
        if ($actor->can('delete', $warehouse)) {
            $html .= '<button type="button" class="btn btn-sm btn-danger btn-wave js-warehouse-delete" data-url="'
                .e(route('admin.warehouses.destroy', $warehouse)).'">Delete</button>';
        }
        $html .= '</div>';

        return $html;
    }

    public function create(): View
    {
        $this->authorize('create', Warehouse::class);

        return view('admin.warehouses.create');
    }

    public function store(StoreWarehouseRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();
            $data['is_active'] = $request->boolean('is_active', true);
            Warehouse::query()->create($data);

            return response()->json([
                'status' => true,
                'message' => 'Warehouse created successfully.',
            ], 201);
        } catch (Throwable $e) {
            Log::error('WarehouseController@store failed', ['message' => $e->getMessage()]);

            return response()->json([
                'status' => false,
                'message' => 'Could not create warehouse.',
            ], 500);
        }
    }

    public function edit(Warehouse $warehouse): View
    {
        $this->authorize('update', $warehouse);

        return view('admin.warehouses.edit', ['warehouse' => $warehouse]);
    }

    public function update(UpdateWarehouseRequest $request, Warehouse $warehouse): JsonResponse
    {
        try {
            $data = $request->validated();
            $data['is_active'] = $request->boolean('is_active', true);
            $warehouse->update($data);

            return response()->json([
                'status' => true,
                'message' => 'Warehouse updated successfully.',
            ]);
        } catch (Throwable $e) {
            Log::error('WarehouseController@update failed', ['message' => $e->getMessage(), 'id' => $warehouse->id]);

            return response()->json([
                'status' => false,
                'message' => 'Could not update warehouse.',
            ], 500);
        }
    }

    public function destroy(Warehouse $warehouse): JsonResponse
    {
        $this->authorize('delete', $warehouse);

        try {
            $warehouse->delete();

            return response()->json([
                'status' => true,
                'message' => 'Warehouse deleted successfully.',
            ]);
        } catch (Throwable $e) {
            Log::error('WarehouseController@destroy failed', ['message' => $e->getMessage(), 'id' => $warehouse->id]);

            return response()->json([
                'status' => false,
                'message' => 'Could not delete warehouse.',
            ], 500);
        }
    }
}
