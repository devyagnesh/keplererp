<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $goods_receipt_id
 * @property int $item_id
 * @property string $quantity
 * @property string|null $accepted_qty
 * @property string|null $rejected_qty
 * @property string|null $batch_no
 * @property string|null $serial_no
 * @property Carbon|null $expiry_date
 */
class GoodsReceiptLine extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'goods_receipt_id',
        'item_id',
        'quantity',
        'accepted_qty',
        'rejected_qty',
        'qc_status',
        'qc_remarks',
        'batch_no',
        'serial_no',
        'expiry_date',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
            'accepted_qty' => 'decimal:4',
            'rejected_qty' => 'decimal:4',
            'expiry_date' => 'date',
        ];
    }

    /**
     * @return BelongsTo<GoodsReceipt, $this>
     */
    public function goodsReceipt(): BelongsTo
    {
        return $this->belongsTo(GoodsReceipt::class);
    }

    /**
     * @return BelongsTo<Item, $this>
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }
}
