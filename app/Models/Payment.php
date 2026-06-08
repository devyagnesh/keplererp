<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Vendor payment or customer receipt (SRS finance cycle).
 *
 * @property int $id
 * @property string $payment_number
 * @property string $payment_type
 * @property int|null $vendor_payable_id
 * @property int|null $invoice_id
 * @property int|null $vendor_id
 * @property int|null $customer_id
 * @property string $amount
 * @property string $payment_method
 * @property string|null $utr_reference
 * @property \Illuminate\Support\Carbon $payment_date
 * @property int|null $created_by
 */
class Payment extends Model
{
    use SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'payment_number',
        'payment_type',
        'vendor_payable_id',
        'invoice_id',
        'vendor_id',
        'customer_id',
        'amount',
        'payment_method',
        'utr_reference',
        'payment_date',
        'created_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'payment_date' => 'date',
        ];
    }

    /**
     * @return BelongsTo<VendorPayable, $this>
     */
    public function vendorPayable(): BelongsTo
    {
        return $this->belongsTo(VendorPayable::class);
    }

    /**
     * @return BelongsTo<Invoice, $this>
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * @return BelongsTo<Vendor, $this>
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    /**
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
