<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Stock item / SKU master.
 *
 * @property int $id
 * @property string $sku
 * @property string $name
 * @property string $uom
 * @property string $reorder_level
 * @property bool $is_active
 * @property string $hsn_code
 * @property string $gst_rate
 * @property string $item_type
 * @property bool $is_batch_tracked
 * @property bool $is_serial_tracked
 */
class Item extends Model
{
    use SoftDeletes;

    /**
     * @var list<string>
     */
    protected $appends = [
        'display_label',
    ];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'sku',
        'name',
        'uom',
        'reorder_level',
        'is_active',
        'hsn_code',
        'gst_rate',
        'item_type',
        'is_batch_tracked',
        'is_serial_tracked',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'reorder_level' => 'decimal:4',
            'is_active' => 'boolean',
            'gst_rate' => 'decimal:2',
            'is_batch_tracked' => 'boolean',
            'is_serial_tracked' => 'boolean',
        ];
    }

    /**
     * @return HasMany<InventoryBalance, $this>
     */
    public function inventoryBalances(): HasMany
    {
        return $this->hasMany(InventoryBalance::class);
    }

    /**
     * Human-readable label for selects, tables, and PDFs: "Item name (SKU)".
     */
    public function getDisplayLabelAttribute(): string
    {
        return self::formatLabel($this->name, $this->sku);
    }

    public static function formatLabel(?string $name, ?string $sku): string
    {
        $name = trim((string) $name);
        $sku = trim((string) $sku);

        if ($name !== '' && $sku !== '') {
            return "{$name} ({$sku})";
        }

        if ($name !== '') {
            return $name;
        }

        if ($sku !== '') {
            return $sku;
        }

        return '—';
    }
}
