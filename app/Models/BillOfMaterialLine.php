<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $bill_of_material_id
 * @property int $component_item_id
 * @property string $quantity_per
 */
class BillOfMaterialLine extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'bill_of_material_id',
        'component_item_id',
        'quantity_per',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity_per' => 'decimal:6',
        ];
    }

    /**
     * @return BelongsTo<BillOfMaterial, $this>
     */
    public function billOfMaterial(): BelongsTo
    {
        return $this->belongsTo(BillOfMaterial::class);
    }

    /**
     * @return BelongsTo<Item, $this>
     */
    public function componentItem(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'component_item_id');
    }
}
