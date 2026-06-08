<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CustomerAddress extends Model
{
    use SoftDeletes;

    /** @var list<string> */
    protected $fillable = [
        'customer_id',
        'label',
        'address_line1',
        'address_line2',
        'city',
        'state_code',
        'pincode',
        'is_default_shipping',
    ];

    protected function casts(): array
    {
        return ['is_default_shipping' => 'boolean'];
    }

    /** @return BelongsTo<Customer, $this> */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
