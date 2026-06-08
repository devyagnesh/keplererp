<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductionStockReservation extends Model
{
    /** @var list<string> */
    protected $fillable = ['production_order_id', 'item_id', 'warehouse_id', 'quantity', 'status'];

    protected function casts(): array
    {
        return ['quantity' => 'decimal:4'];
    }

    /** @return BelongsTo<ProductionOrder, $this> */
    public function productionOrder(): BelongsTo
    {
        return $this->belongsTo(ProductionOrder::class);
    }
}
