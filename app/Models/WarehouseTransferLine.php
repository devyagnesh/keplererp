<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Line item on a warehouse transfer document.
 */
class WarehouseTransferLine extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'warehouse_transfer_id',
        'item_id',
        'qty_requested',
        'qty_dispatched',
        'qty_received',
        'batch_no',
        'serial_no',
        'variance_reason',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'qty_requested' => 'decimal:4',
            'qty_dispatched' => 'decimal:4',
            'qty_received' => 'decimal:4',
        ];
    }

    /**
     * @return BelongsTo<WarehouseTransfer, $this>
     */
    public function transfer(): BelongsTo
    {
        return $this->belongsTo(WarehouseTransfer::class, 'warehouse_transfer_id');
    }

    /**
     * @return BelongsTo<Item, $this>
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }
}
