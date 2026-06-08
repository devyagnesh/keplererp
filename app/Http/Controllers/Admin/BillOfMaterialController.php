<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBillOfMaterialRequest;
use App\Models\BillOfMaterial;
use App\Models\Item;
use App\Support\ErpDataTable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Throwable;

class BillOfMaterialController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', BillOfMaterial::class);

        return view('admin.production.boms-index');
    }

    public function data(Request $request): JsonResponse
    {
        $this->authorize('viewAny', BillOfMaterial::class);

        $query = BillOfMaterial::query()
            ->select(['bill_of_materials.id', 'parent_item_id', 'version', 'is_active', 'created_at'])
            ->with(['parentItem:id,sku,name']);

        $payload = ErpDataTable::run(
            $query,
            $request,
            function ($q, string $term): void {
                $q->whereHas('parentItem', function ($iq) use ($term): void {
                    $iq->where('sku', 'like', '%'.$term.'%')
                        ->orWhere('name', 'like', '%'.$term.'%');
                });
            },
            ['id', 'version', 'is_active', 'created_at'],
        );

        $data = $payload['rows']->map(function (BillOfMaterial $row) {
            return [
                'parent_item' => $row->parentItem?->display_label ?? '—',
                'version' => (string) $row->version,
                'is_active' => $row->is_active ? 'Yes' : 'No',
                'created_at' => $row->created_at?->format('Y-m-d H:i'),
            ];
        })->values()->all();

        return response()->json([
            'draw' => $payload['draw'],
            'recordsTotal' => $payload['recordsTotal'],
            'recordsFiltered' => $payload['recordsFiltered'],
            'data' => $data,
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', BillOfMaterial::class);

        return view('admin.production.boms-create', [
            'items' => Item::query()->where('is_active', true)->orderBy('sku')->get(['id', 'sku', 'name']),
        ]);
    }

    public function store(StoreBillOfMaterialRequest $request): JsonResponse
    {
        try {
            DB::transaction(function () use ($request): void {
                $v = $request->validated();
                $bom = BillOfMaterial::query()->create([
                    'parent_item_id' => $v['parent_item_id'],
                    'version' => $v['version'],
                    'is_active' => true,
                    'notes' => $v['notes'] ?? null,
                ]);
                foreach ($v['lines'] as $line) {
                    $bom->lines()->create([
                        'component_item_id' => $line['component_item_id'],
                        'quantity_per' => $line['quantity_per'],
                    ]);
                }
            });

            return response()->json([
                'status' => true,
                'message' => 'Bill of materials created successfully.',
            ], 201);
        } catch (Throwable $e) {
            Log::error('BillOfMaterialController@store failed', ['message' => $e->getMessage()]);

            return response()->json([
                'status' => false,
                'message' => 'Could not create BOM.',
            ], 500);
        }
    }
}
