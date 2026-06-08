<?php

namespace App\Models;

use App\Enums\VendorStatus;
use Database\Factories\VendorFactory;
use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Supplier / vendor master (purchase module).
 *
 * @property int $id
 * @property string $vendor_code
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
 * @property VendorStatus $status
 * @property \Illuminate\Support\Carbon|null $approved_at
 * @property int|null $approved_by
 * @property int|null $created_by
 */
class Vendor extends Model implements AuthenticatableContract
{
    /** @use HasFactory<VendorFactory> */
    use Authenticatable, HasFactory, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'vendor_code',
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
        'portal_enabled',
        'portal_password',
        'approved_at',
        'approved_by',
        'created_by',
        'vendor_type',
        'credit_limit',
        'bank_name',
        'bank_account_no',
        'bank_ifsc',
        'rating',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => VendorStatus::class,
            'approved_at' => 'datetime',
            'portal_enabled' => 'boolean',
            'portal_password' => 'hashed',
            'credit_limit' => 'decimal:2',
            'rating' => 'decimal:2',
        ];
    }

    /**
     * Password for vendor portal authentication.
     */
    public function getAuthPassword(): string
    {
        return (string) $this->portal_password;
    }

    /**
     * User who approved this vendor.
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * User who created this vendor record.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return HasMany<VendorDocument, $this>
     */
    public function documents(): HasMany
    {
        return $this->hasMany(VendorDocument::class);
    }
}
