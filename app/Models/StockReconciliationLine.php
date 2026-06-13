<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Physical vs system quantity line on a stock reconciliation.
 */
class StockReconciliationLine extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'stock_reconciliation_id',
        'item_id',
        'system_qty',
        'physical_qty',
        'variance_qty',
        'reason',
        'adjustment_posted',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'system_qty' => 'decimal:4',
            'physical_qty' => 'decimal:4',
            'variance_qty' => 'decimal:4',
            'adjustment_posted' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<StockReconciliation, $this>
     */
    public function reconciliation(): BelongsTo
    {
        return $this->belongsTo(StockReconciliation::class, 'stock_reconciliation_id');
    }

    /**
     * @return BelongsTo<Item, $this>
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }
}
