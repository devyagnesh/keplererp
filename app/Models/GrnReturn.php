<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Vendor return against a posted GRN (SRS purchase returns).
 */
class GrnReturn extends Model
{
    use SoftDeletes;

    /** @var list<string> */
    protected $fillable = [
        'return_number',
        'goods_receipt_id',
        'vendor_id',
        'status',
        'reason',
        'created_by',
        'posted_at',
    ];

    protected function casts(): array
    {
        return ['posted_at' => 'datetime'];
    }

    /** @return BelongsTo<GoodsReceipt, $this> */
    public function goodsReceipt(): BelongsTo
    {
        return $this->belongsTo(GoodsReceipt::class);
    }

    /** @return BelongsTo<Vendor, $this> */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    /** @return HasMany<GrnReturnLine, $this> */
    public function lines(): HasMany
    {
        return $this->hasMany(GrnReturnLine::class);
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
