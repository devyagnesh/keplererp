<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreItemRequest;
use App\Http\Requests\UpdateItemRequest;
use App\Models\Item;
use App\Models\User;
use App\Support\ErpDataTable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Throwable;

class ItemController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Item::class);

        return view('admin.items.index');
    }

    public function data(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Item::class);

        $query = Item::query()->select([
            'id', 'sku', 'name', 'uom', 'reorder_level', 'is_active',
            'is_batch_tracked', 'is_serial_tracked', 'created_at',
        ]);

        $payload = ErpDataTable::run(
            $query,
            $request,
            function ($q, string $term): void {
                $q->where('sku', 'like', '%'.$term.'%')
                    ->orWhere('name', 'like', '%'.$term.'%');
            },
            ['id', 'sku', 'name', 'uom', 'reorder_level', 'is_active', 'created_at'],
        );

        $actor = $request->user();
        if ($actor === null) {
            abort(403);
        }

        $data = $payload['rows']->map(function (Item $row) use ($actor) {
            return [
                'sku' => $row->sku,
                'name' => $row->name,
                'uom' => $row->uom,
                'reorder_level' => (string) $row->reorder_level,
                'tracking' => $this->trackingLabel($row),
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

    protected function buildActionHtml(Item $item, User $actor): string
    {
        $html = '<div class="btn-list d-flex flex-wrap gap-1">';
        if ($actor->can('update', $item)) {
            $html .= '<a href="'.e(route('admin.items.edit', $item)).'" class="btn btn-sm btn-primary btn-wave">Edit</a>';
        }
        if ($actor->can('delete', $item)) {
            $html .= '<button type="button" class="btn btn-sm btn-danger btn-wave js-item-delete" data-url="'
                .e(route('admin.items.destroy', $item)).'">Delete</button>';
        }
        $html .= '</div>';

        return $html;
    }

    public function create(): View
    {
        $this->authorize('create', Item::class);

        return view('admin.items.create');
    }

    public function store(StoreItemRequest $request): JsonResponse
    {
        try {
            $data = $this->normalizeItemPayload($request->validated(), $request);
            Item::query()->create($data);

            return response()->json([
                'status' => true,
                'message' => 'Item created successfully.',
            ], 201);
        } catch (Throwable $e) {
            Log::error('ItemController@store failed', ['message' => $e->getMessage()]);

            return response()->json([
                'status' => false,
                'message' => 'Could not create item.',
            ], 500);
        }
    }

    public function edit(Item $item): View
    {
        $this->authorize('update', $item);

        return view('admin.items.edit', ['item' => $item]);
    }

    public function update(UpdateItemRequest $request, Item $item): JsonResponse
    {
        try {
            $data = $this->normalizeItemPayload($request->validated(), $request);
            $item->update($data);

            return response()->json([
                'status' => true,
                'message' => 'Item updated successfully.',
            ]);
        } catch (Throwable $e) {
            Log::error('ItemController@update failed', ['message' => $e->getMessage(), 'id' => $item->id]);

            return response()->json([
                'status' => false,
                'message' => 'Could not update item.',
            ], 500);
        }
    }

    public function destroy(Item $item): JsonResponse
    {
        $this->authorize('delete', $item);

        try {
            $item->delete();

            return response()->json([
                'status' => true,
                'message' => 'Item deleted successfully.',
            ]);
        } catch (Throwable $e) {
            Log::error('ItemController@destroy failed', ['message' => $e->getMessage(), 'id' => $item->id]);

            return response()->json([
                'status' => false,
                'message' => 'Could not delete item.',
            ], 500);
        }
    }

    protected function trackingLabel(Item $item): string
    {
        if ($item->is_serial_tracked) {
            return 'Serial';
        }
        if ($item->is_batch_tracked) {
            return 'Batch';
        }

        return '—';
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function normalizeItemPayload(array $data, FormRequest $request): array
    {
        $data['is_active'] = $request->boolean('is_active', true);
        $data['is_batch_tracked'] = $request->boolean('is_batch_tracked', false);
        $data['is_serial_tracked'] = $request->boolean('is_serial_tracked', false);
        if ($data['is_serial_tracked']) {
            $data['is_batch_tracked'] = false;
        }

        return $data;
    }
}
