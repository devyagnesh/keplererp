<?php

namespace App\Models;

use App\Enums\CustomerStatus;
use App\Models\PriceList;
use Database\Factories\CustomerFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Customer master (sales module).
 *
 * @property int $id
 * @property string $customer_code
 * @property string $name
 * @property string|null $contact_person
 * @property string|null $email
 * @property string $phone
 * @property string|null $gstin
 * @property string|null $pan
 * @property string $address_line1
 * @property string|null $address_line2
 * @property string $city
 * @property string $state_code
 * @property string $pincode
 * @property string|null $payment_terms
 * @property string|null $notes
 * @property CustomerStatus $status
 * @property int|null $created_by
 * @property string $credit_limit
 * @property string $credit_used
 * @property int $payment_terms_days
 */
class Customer extends Model
{
    /** @use HasFactory<CustomerFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'customer_code',
        'name',
        'contact_person',
        'email',
        'phone',
        'gstin',
        'pan',
        'address_line1',
        'address_line2',
        'city',
        'state_code',
        'pincode',
        'payment_terms',
        'notes',
        'status',
        'created_by',
        'credit_limit',
        'credit_used',
        'payment_terms_days',
        'price_list_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => CustomerStatus::class,
            'credit_limit' => 'decimal:2',
            'credit_used' => 'decimal:2',
        ];
    }

    /**
     * User who created this customer record.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return HasMany<CustomerAddress, $this>
     */
    public function addresses(): HasMany
    {
        return $this->hasMany(CustomerAddress::class);
    }

    /**
     * @return BelongsTo<PriceList, $this>
     */
    public function priceList(): BelongsTo
    {
        return $this->belongsTo(PriceList::class);
    }
}
