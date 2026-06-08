<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class SalesEnquiry extends Model
{
    use SoftDeletes;

    /** @var list<string> */
    protected $fillable = [
        'enquiry_number',
        'customer_id',
        'contact_name',
        'phone',
        'email',
        'status',
        'notes',
        'created_by',
    ];

    /** @return BelongsTo<Customer, $this> */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
