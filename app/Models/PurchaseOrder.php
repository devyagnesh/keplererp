<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $po_number
 * @property int $vendor_id
 * @property \Illuminate\Support\Carbon $order_date
 * @property string $status
 * @property int|null $created_by
 * @property string|null $notes
 * @property int|null $pr_id
 * @property int|null $warehouse_id
 * @property \Illuminate\Support\Carbon|null $expected_delivery
 * @property int $payment_terms_days
 * @property string $subtotal
 * @property string $discount_amount
 * @property string $taxable_amount
 * @property string $cgst_amount
 * @property string $sgst_amount
 * @property string $igst_amount
 * @property string $total_amount
 * @property \Illuminate\Support\Carbon|null $finance_approved_at
 * @property int|null $finance_approved_by
 * @property string|null $rejected_reason
 */
class PurchaseOrder extends Model
{
    use SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'po_number',
        'vendor_id',
        'order_date',
        'status',
        'created_by',
        'notes',
        'pr_id',
        'warehouse_id',
        'expected_delivery',
        'payment_terms_days',
        'subtotal',
        'discount_amount',
        'taxable_amount',
        'cgst_amount',
        'sgst_amount',
        'igst_amount',
        'total_amount',
        'finance_approved_at',
        'finance_approved_by',
        'rejected_reason',
        'approval_level',
        'vendor_delivery_status',
        'vendor_delivery_notes',
        'vendor_delivery_updated_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'order_date' => 'date',
            'expected_delivery' => 'date',
            'finance_approved_at' => 'datetime',
            'vendor_delivery_updated_at' => 'datetime',
            'subtotal' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'taxable_amount' => 'decimal:2',
            'cgst_amount' => 'decimal:2',
            'sgst_amount' => 'decimal:2',
            'igst_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
        ];
    }

    /**
     * @return BelongsTo<PurchaseRequisition, $this>
     */
    public function purchaseRequisition(): BelongsTo
    {
        return $this->belongsTo(PurchaseRequisition::class, 'pr_id');
    }

    /**
     * @return BelongsTo<Warehouse, $this>
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * @return BelongsTo<Vendor, $this>
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return HasMany<PurchaseOrderLine, $this>
     */
    public function lines(): HasMany
    {
        return $this->hasMany(PurchaseOrderLine::class);
    }
}
