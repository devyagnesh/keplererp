<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Sales tax invoice linked to a sales order.
 *
 * @property int $id
 * @property string $invoice_number
 * @property int $sales_order_id
 * @property int $customer_id
 * @property \Illuminate\Support\Carbon $invoice_date
 * @property \Illuminate\Support\Carbon $due_date
 * @property string $place_of_supply
 * @property string $subtotal
 * @property string $discount_amount
 * @property string $taxable_amount
 * @property string $cgst_amount
 * @property string $sgst_amount
 * @property string $igst_amount
 * @property string $total_amount
 * @property string $status
 * @property int|null $created_by
 */
class Invoice extends Model
{
    use SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'invoice_number',
        'sales_order_id',
        'customer_id',
        'invoice_date',
        'due_date',
        'place_of_supply',
        'subtotal',
        'discount_amount',
        'taxable_amount',
        'cgst_amount',
        'sgst_amount',
        'igst_amount',
        'total_amount',
        'amount_paid',
        'status',
        'irn',
        'ack_no',
        'einvoice_qr',
        'irn_generated_at',
        'created_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'invoice_date' => 'date',
            'due_date' => 'date',
            'irn_generated_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<SalesOrder, $this>
     */
    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class);
    }

    /**
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * @return HasMany<InvoiceItem, $this>
     */
    public function invoiceItems(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }
}
