<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\InventoryAdjustRequest;
use App\Http\Requests\InventoryTransferRequest;
use App\Models\Item;
use App\Models\Warehouse;
use App\Services\InventoryStockService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use InvalidArgumentException;
use Throwable;

class InventoryStockController extends Controller
{
    public function __construct(
        protected InventoryStockService $stockService
    ) {}

    public function adjustForm(): View
    {
        if (! auth()->user()?->can('inventory.adjust')) {
            abort(403);
        }

        return view('admin.inventory.adjust', [
            'warehouses' => Warehouse::query()->where('is_active', true)->orderBy('code')->get(['id', 'code', 'name']),
            'items' => Item::query()->where('is_active', true)->orderBy('sku')->get([
                'id', 'sku', 'name', 'is_batch_tracked', 'is_serial_tracked',
            ]),
        ]);
    }

    public function adjust(InventoryAdjustRequest $request): JsonResponse
    {
        $user = $request->user();
        if ($user === null) {
            abort(403);
        }

        try {
            $v = $request->validated();
            $this->stockService->adjust(
                (int) $v['warehouse_id'],
                (int) $v['item_id'],
                (string) $v['signed_delta'],
                $user->id,
                [
                    'notes' => $v['notes'] ?? null,
                    'batch_no' => $v['batch_no'] ?? null,
                    'serial_no' => $v['serial_no'] ?? null,
                ]
            );

            return response()->json([
                'status' => true,
                'message' => 'Stock adjusted successfully.',
            ]);
        } catch (InvalidArgumentException $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (Throwable $e) {
            Log::error('InventoryStockController@adjust failed', ['message' => $e->getMessage()]);

            return response()->json([
                'status' => false,
                'message' => 'Could not adjust stock.',
            ], 500);
        }
    }

    public function transferForm(): View
    {
        if (! auth()->user()?->can('inventory.transfer')) {
            abort(403);
        }

        return view('admin.inventory.transfer', [
            'warehouses' => Warehouse::query()->where('is_active', true)->orderBy('code')->get(['id', 'code', 'name']),
            'items' => Item::query()->where('is_active', true)->orderBy('sku')->get([
                'id', 'sku', 'name', 'is_batch_tracked', 'is_serial_tracked',
            ]),
        ]);
    }

    public function transfer(InventoryTransferRequest $request): JsonResponse
    {
        $user = $request->user();
        if ($user === null) {
            abort(403);
        }

        try {
            $v = $request->validated();
            $this->stockService->transfer(
                (int) $v['from_warehouse_id'],
                (int) $v['to_warehouse_id'],
                (int) $v['item_id'],
                (string) $v['quantity'],
                $user->id,
                $v['notes'] ?? null,
                [
                    'batch_no' => $v['batch_no'] ?? null,
                    'serial_no' => $v['serial_no'] ?? null,
                ]
            );

            return response()->json([
                'status' => true,
                'message' => 'Stock transferred successfully.',
            ]);
        } catch (InvalidArgumentException $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (Throwable $e) {
            Log::error('InventoryStockController@transfer failed', ['message' => $e->getMessage()]);

            return response()->json([
                'status' => false,
                'message' => 'Could not transfer stock.',
            ], 500);
        }
    }
}
