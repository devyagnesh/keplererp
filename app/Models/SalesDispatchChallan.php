<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Dispatch challan issued when a sales order is dispatched (SRS sales logistics).
 */
class SalesDispatchChallan extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'challan_number',
        'sales_order_id',
        'customer_id',
        'warehouse_id',
        'dispatched_at',
        'eway_bill_no',
        'eway_qr',
        'eway_generated_at',
        'created_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'dispatched_at' => 'datetime',
            'eway_generated_at' => 'datetime',
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
     * @return BelongsTo<Warehouse, $this>
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }
}
