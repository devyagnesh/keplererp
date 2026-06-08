<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Vendor compliance / contract document upload (SRS vendor master).
 */
class VendorDocument extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'vendor_id',
        'document_type',
        'original_name',
        'storage_path',
        'mime_type',
        'size_bytes',
        'uploaded_by',
    ];

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
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
