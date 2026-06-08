<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductionOrderMaterial extends Model
{
    /** @var list<string> */
    protected $fillable = ['production_order_id', 'item_id', 'planned_qty', 'actual_qty'];

    protected function casts(): array
    {
        return [
            'planned_qty' => 'decimal:4',
            'actual_qty' => 'decimal:4',
        ];
    }

    /** @return BelongsTo<ProductionOrder, $this> */
    public function productionOrder(): BelongsTo
    {
        return $this->belongsTo(ProductionOrder::class);
    }

    /** @return BelongsTo<Item, $this> */
    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }
}
