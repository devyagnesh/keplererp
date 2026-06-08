<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Services\BatchSerialInventoryService;
use Illuminate\Http\JsonResponse;

/**
 * JSON endpoints for batch/serial UI (options, tracking flags).
 */
class ItemTrackingController extends Controller
{
    public function __construct(
        protected BatchSerialInventoryService $batchSerial
    ) {}

    /**
     * Map of item id → tracking flags for client-side forms.
     */
    public function trackingMap(): JsonResponse
    {
        $items = Item::query()
            ->where('is_active', true)
            ->get(['id', 'is_batch_tracked', 'is_serial_tracked']);

        $map = [];
        foreach ($items as $item) {
            $map[(string) $item->id] = [
                'is_batch_tracked' => (bool) $item->is_batch_tracked,
                'is_serial_tracked' => (bool) $item->is_serial_tracked,
            ];
        }

        return response()->json([
            'status' => true,
            'items' => $map,
        ]);
    }

    /**
     * Available batches for outbound selection.
     */
    public function batches(int $warehouse, int $item): JsonResponse
    {
        return response()->json([
            'status' => true,
            'data' => $this->batchSerial->availableBatches($warehouse, $item),
        ]);
    }

    /**
     * Available serials for outbound selection.
     */
    public function serials(int $warehouse, int $item): JsonResponse
    {
        return response()->json([
            'status' => true,
            'data' => $this->batchSerial->availableSerials($warehouse, $item),
        ]);
    }
}
