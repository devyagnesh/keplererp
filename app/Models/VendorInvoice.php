<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Vendor tax invoice uploaded against a GRN payable (3-way match).
 */
class VendorInvoice extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'vendor_id',
        'vendor_payable_id',
        'vendor_invoice_number',
        'invoice_date',
        'amount',
        'original_name',
        'storage_path',
        'mime_type',
        'size_bytes',
        'status',
        'po_amount',
        'grn_amount',
        'match_status',
        'match_notes',
        'uploaded_by_vendor',
        'matched_by',
        'matched_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'invoice_date' => 'date',
            'matched_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Vendor, $this>
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    /**
     * @return BelongsTo<VendorPayable, $this>
     */
    public function vendorPayable(): BelongsTo
    {
        return $this->belongsTo(VendorPayable::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function matcher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'matched_by');
    }
}
