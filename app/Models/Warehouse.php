<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Storage location for inventory.
 *
 * @property int $id
 * @property string $code
 * @property string $name
 * @property string|null $city
 * @property bool $is_active
 */
class Warehouse extends Model
{
    use SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'code',
        'name',
        'city',
        'is_active',
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
     * @return HasMany<InventoryBalance, $this>
     */
    public function inventoryBalances(): HasMany
    {
        return $this->hasMany(InventoryBalance::class);
    }
}
