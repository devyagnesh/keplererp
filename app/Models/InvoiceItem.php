<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $invoice_id
 * @property int $item_id
 * @property string $quantity
 * @property string $unit_price
 * @property string $taxable_value
 * @property string $cgst
 * @property string $sgst
 * @property string $igst
 */
class InvoiceItem extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'invoice_id',
        'item_id',
        'quantity',
        'unit_price',
        'taxable_value',
        'cgst',
        'sgst',
        'igst',
    ];

    /**
     * @return BelongsTo<Invoice, $this>
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * @return BelongsTo<Item, $this>
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }
}
