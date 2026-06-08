<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Reserved stock for a confirmed sales order.
 *
 * @property int $id
 * @property int $sales_order_id
 * @property int $item_id
 * @property int $warehouse_id
 * @property string $quantity
 * @property string $status
 */
class StockReservation extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'sales_order_id',
        'item_id',
        'warehouse_id',
        'quantity',
        'status',
    ];

    /**
     * @return BelongsTo<SalesOrder, $this>
     */
    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class);
    }

    /**
     * @return BelongsTo<Item, $this>
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    /**
     * @return BelongsTo<Warehouse, $this>
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }
}
