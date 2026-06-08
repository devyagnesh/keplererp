<?php

namespace App\Services;

use App\Models\SalesOrder;
use InvalidArgumentException;

/**
 * Pick & pack workflow — marks order as processing (SRS dispatch prep).
 */
class SalesOrderProcessingService
{
    public function markProcessing(SalesOrder $order, ?string $courier, ?string $tracking, ?string $transporter): SalesOrder
    {
        if (! in_array($order->status, ['confirmed'], true)) {
            throw new InvalidArgumentException('Only confirmed orders can move to processing.');
        }

        $order->update([
            'status' => 'processing',
            'processing_at' => now(),
            'courier_name' => $courier,
            'tracking_number' => $tracking,
            'transporter_name' => $transporter,
        ]);

        return $order->fresh();
    }
}
