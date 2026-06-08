<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Accounts payable stub linked to a posted GRN.
 *
 * @property int $id
 * @property int $goods_receipt_id
 * @property int $vendor_id
 * @property string $amount
 * @property string $status
 */
class VendorPayable extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'goods_receipt_id',
        'vendor_id',
        'amount',
        'amount_paid',
        'status',
    ];

    /**
     * @return BelongsTo<GoodsReceipt, $this>
     */
    public function goodsReceipt(): BelongsTo
    {
        return $this->belongsTo(GoodsReceipt::class);
    }

    /**
     * @return BelongsTo<Vendor, $this>
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    /**
     * @return HasMany<VendorInvoice, $this>
     */
    public function vendorInvoices(): HasMany
    {
        return $this->hasMany(VendorInvoice::class);
    }
}
