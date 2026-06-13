<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Goods received note — posts stock into a warehouse.
 *
 * @property int $id
 * @property string $grn_number
 * @property int|null $purchase_order_id
 * @property int $vendor_id
 * @property int $warehouse_id
 * @property Carbon $received_at
 * @property int|null $created_by
 * @property string|null $notes
 * @property string $status
 * @property Carbon|null $posted_at
 */
class GoodsReceipt extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'grn_number',
        'purchase_order_id',
        'vendor_id',
        'warehouse_id',
        'received_at',
        'created_by',
        'notes',
        'status',
        'posted_at',
        'qc_officer_name',
        'qc_photo_path',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'received_at' => 'datetime',
            'posted_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<PurchaseOrder, $this>
     */
    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    /**
     * @return BelongsTo<Vendor, $this>
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    /**
     * @return BelongsTo<Warehouse, $this>
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return HasMany<GoodsReceiptLine, $this>
     */
    public function lines(): HasMany
    {
        return $this->hasMany(GoodsReceiptLine::class);
    }
}
