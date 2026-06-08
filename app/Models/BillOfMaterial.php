<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $parent_item_id
 * @property int $version
 * @property bool $is_active
 * @property string|null $notes
 */
class BillOfMaterial extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'parent_item_id',
        'version',
        'is_active',
        'notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Item, $this>
     */
    public function parentItem(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'parent_item_id');
    }

    /**
     * @return HasMany<BillOfMaterialLine, $this>
     */
    public function lines(): HasMany
    {
        return $this->hasMany(BillOfMaterialLine::class);
    }

    /**
     * Latest active BOM for a finished-good / parent item.
     */
    public static function activeForItem(int $parentItemId): ?self
    {
        return self::query()
            ->where('parent_item_id', $parentItemId)
            ->where('is_active', true)
            ->orderByDesc('version')
            ->first();
    }
}
