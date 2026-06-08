<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * On-hand quantity per warehouse and item.
 *
 * @property int $id
 * @property int $warehouse_id
 * @property int $item_id
 * @property string $quantity
 */
class InventoryBalance extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'warehouse_id',
        'item_id',
        'quantity',
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
     * @return BelongsTo<Item, $this>
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }
}
