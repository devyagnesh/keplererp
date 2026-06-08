<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $order_number
 * @property int $customer_id
 * @property \Illuminate\Support\Carbon $order_date
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $dispatched_at
 * @property int|null $created_by
 * @property string|null $notes
 * @property int|null $quotation_id
 * @property int|null $warehouse_id
 * @property \Illuminate\Support\Carbon|null $expected_dispatch
 * @property int $payment_terms_days
 * @property string $subtotal
 * @property string $discount_amount
 * @property string $taxable_amount
 * @property string $cgst_amount
 * @property string $sgst_amount
 * @property string $igst_amount
 * @property string $total_amount
 */
class SalesOrder extends Model
{
    use SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'order_number',
        'customer_id',
        'order_date',
        'status',
        'dispatched_at',
        'created_by',
        'notes',
        'quotation_id',
        'warehouse_id',
        'expected_dispatch',
        'payment_terms_days',
        'subtotal',
        'discount_amount',
        'taxable_amount',
        'cgst_amount',
        'sgst_amount',
        'igst_amount',
        'total_amount',
        'customer_address_id',
        'courier_name',
        'tracking_number',
        'transporter_name',
        'processing_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'order_date' => 'date',
            'dispatched_at' => 'datetime',
            'processing_at' => 'datetime',
            'expected_dispatch' => 'date',
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
     * @return BelongsTo<Warehouse, $this>
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * @return BelongsTo<SalesQuotation, $this>
     */
    public function quotation(): BelongsTo
    {
        return $this->belongsTo(SalesQuotation::class, 'quotation_id');
    }

    /**
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return HasMany<SalesOrderLine, $this>
     */
    public function lines(): HasMany
    {
        return $this->hasMany(SalesOrderLine::class);
    }

    /**
     * @return HasOne<Invoice, $this>
     */
    public function postedInvoice(): HasOne
    {
        return $this->hasOne(Invoice::class)->ofMany(['id' => 'max'], function ($query): void {
            $query->where('status', 'posted');
        });
    }

    /**
     * @return HasOne<SalesDispatchChallan, $this>
     */
    public function dispatchChallan(): HasOne
    {
        return $this->hasOne(SalesDispatchChallan::class);
    }
}
