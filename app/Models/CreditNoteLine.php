<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CreditNoteLine extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'credit_note_id',
        'item_id',
        'quantity',
        'unit_price',
        'taxable_value',
        'gst_rate',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
            'unit_price' => 'decimal:4',
            'taxable_value' => 'decimal:2',
            'gst_rate' => 'decimal:2',
        ];
    }

    /** @return BelongsTo<CreditNote, $this> */
    public function creditNote(): BelongsTo
    {
        return $this->belongsTo(CreditNote::class);
    }
}
