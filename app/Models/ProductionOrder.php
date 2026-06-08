<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $wo_number
 * @property int $item_id
 * @property string $qty_planned
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $planned_start
 * @property \Illuminate\Support\Carbon|null $planned_end
 * @property int|null $created_by
 * @property string|null $notes
 * @property int|null $bom_id
 * @property int|null $warehouse_id
 * @property string|null $actual_qty
 */
class ProductionOrder extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'wo_number',
        'item_id',
        'qty_planned',
        'status',
        'planned_start',
        'planned_end',
        'created_by',
        'notes',
        'bom_id',
        'warehouse_id',
        'actual_qty',
        'sales_order_id',
        'actual_start',
        'actual_end',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'qty_planned' => 'decimal:4',
            'actual_qty' => 'decimal:4',
            'planned_start' => 'date',
            'planned_end' => 'date',
            'actual_start' => 'datetime',
            'actual_end' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<BillOfMaterial, $this>
     */
    public function billOfMaterial(): BelongsTo
    {
        return $this->belongsTo(BillOfMaterial::class, 'bom_id');
    }

    /**
     * @return BelongsTo<Warehouse, $this>
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * @return BelongsTo<Item, $this>
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
