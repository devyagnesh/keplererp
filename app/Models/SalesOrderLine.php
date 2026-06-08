<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $sales_order_id
 * @property int $item_id
 * @property string $quantity
 * @property string $unit_price
 * @property string $gst_rate
 * @property string $taxable_value
 * @property string $cgst
 * @property string $sgst
 * @property string $igst
 * @property string $line_total
 * @property string|null $batch_no
 * @property string|null $serial_no
 */
class SalesOrderLine extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'sales_order_id',
        'item_id',
        'quantity',
        'unit_price',
        'gst_rate',
        'taxable_value',
        'cgst',
        'sgst',
        'igst',
        'line_total',
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
            'unit_price' => 'decimal:4',
            'gst_rate' => 'decimal:2',
            'taxable_value' => 'decimal:2',
            'cgst' => 'decimal:2',
            'sgst' => 'decimal:2',
            'igst' => 'decimal:2',
            'line_total' => 'decimal:2',
        ];
    }

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
}
