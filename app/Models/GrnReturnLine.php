<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GrnReturnLine extends Model
{
    /** @var list<string> */
    protected $fillable = ['grn_return_id', 'item_id', 'quantity', 'batch_no'];

    protected function casts(): array
    {
        return ['quantity' => 'decimal:4'];
    }

    /** @return BelongsTo<GrnReturn, $this> */
    public function grnReturn(): BelongsTo
    {
        return $this->belongsTo(GrnReturn::class);
    }

    /** @return BelongsTo<Item, $this> */
    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }
}
