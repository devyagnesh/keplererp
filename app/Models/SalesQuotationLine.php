<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $sales_quotation_id
 * @property int $item_id
 * @property string $quantity
 * @property string $unit_price
 */
class SalesQuotationLine extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'sales_quotation_id',
        'item_id',
        'quantity',
        'unit_price',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
            'unit_price' => 'decimal:4',
        ];
    }

    /**
     * @return BelongsTo<SalesQuotation, $this>
     */
    public function salesQuotation(): BelongsTo
    {
        return $this->belongsTo(SalesQuotation::class);
    }

    /**
     * @return BelongsTo<Item, $this>
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }
}
