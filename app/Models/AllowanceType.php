<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Configurable earning allowance (HRA, conveyance, etc.).
 *
 * @property int $id
 * @property string $code
 * @property string $name
 * @property int $sort_order
 * @property bool $is_active
 * @property bool $included_in_esi_gross
 */
class AllowanceType extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'code',
        'name',
        'sort_order',
        'is_active',
        'included_in_esi_gross',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'included_in_esi_gross' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * @return HasMany<EmployeeAllowance, $this>
     */
    public function employeeAllowances(): HasMany
    {
        return $this->hasMany(EmployeeAllowance::class);
    }
}
