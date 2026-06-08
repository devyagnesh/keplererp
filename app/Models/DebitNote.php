<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class DebitNote extends Model
{
    use SoftDeletes;

    /** @var list<string> */
    protected $fillable = [
        'debit_note_number',
        'vendor_id',
        'goods_receipt_id',
        'grn_return_id',
        'amount',
        'status',
        'reason',
        'created_by',
    ];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2'];
    }

    /** @return BelongsTo<Vendor, $this> */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    /** @return BelongsTo<GrnReturn, $this> */
    public function grnReturn(): BelongsTo
    {
        return $this->belongsTo(GrnReturn::class);
    }
}
