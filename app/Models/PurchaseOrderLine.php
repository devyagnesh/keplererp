<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $purchase_order_id
 * @property int $item_id
 * @property string $quantity
 * @property string $unit_cost
 * @property string $gst_rate
 * @property string $taxable_value
 * @property string $cgst
 * @property string $sgst
 * @property string $igst
 * @property string $line_total
 */
class PurchaseOrderLine extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'purchase_order_id',
        'item_id',
        'quantity',
        'unit_cost',
        'gst_rate',
        'taxable_value',
        'cgst',
        'sgst',
        'igst',
        'line_total',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
            'unit_cost' => 'decimal:4',
            'gst_rate' => 'decimal:2',
            'taxable_value' => 'decimal:2',
            'cgst' => 'decimal:2',
            'sgst' => 'decimal:2',
            'igst' => 'decimal:2',
            'line_total' => 'decimal:2',
        ];
    }

    /**
     * @return BelongsTo<PurchaseOrder, $this>
     */
    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    /**
     * @return BelongsTo<Item, $this>
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }
}
