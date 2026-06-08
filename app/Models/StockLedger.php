<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Immutable stock ledger row (append-only).
 *
 * @property int $id
 * @property int $item_id
 * @property int $warehouse_id
 * @property string|null $batch_no
 * @property string|null $serial_no
 * @property \Illuminate\Support\Carbon|null $expiry_date
 * @property string $transaction_type
 * @property string|null $reference_type
 * @property int|null $reference_id
 * @property string|null $qty_in
 * @property string|null $qty_out
 * @property string $balance_qty
 * @property string|null $unit_cost
 * @property int|null $created_by
 */
class StockLedger extends Model
{
    public $timestamps = false;

    /**
     * @var string
     */
    protected $table = 'stock_ledger';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'item_id',
        'warehouse_id',
        'batch_no',
        'serial_no',
        'expiry_date',
        'transaction_type',
        'reference_type',
        'reference_id',
        'qty_in',
        'qty_out',
        'balance_qty',
        'unit_cost',
        'created_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'expiry_date' => 'date',
            'created_at' => 'datetime',
        ];
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
