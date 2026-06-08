<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Audit trail for quantity changes (adjustment, transfer, GRN).
 *
 * @property int $id
 * @property string $movement_type
 * @property int|null $warehouse_id
 * @property int|null $to_warehouse_id
 * @property int $item_id
 * @property string $quantity
 * @property string|null $reference_type
 * @property int|null $reference_id
 * @property string|null $notes
 * @property int|null $created_by
 * @property string|null $batch_no
 * @property string|null $serial_no
 */
class StockMovement extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'movement_type',
        'warehouse_id',
        'to_warehouse_id',
        'item_id',
        'quantity',
        'reference_type',
        'reference_id',
        'notes',
        'created_by',
        'batch_no',
        'serial_no',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
        ];
    }

    /**
     * @return BelongsTo<Warehouse, $this>
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * @return BelongsTo<Warehouse, $this>
     */
    public function toWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'to_warehouse_id');
    }

    /**
     * @return BelongsTo<Item, $this>
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
