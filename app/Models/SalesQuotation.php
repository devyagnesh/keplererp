<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $quote_number
 * @property int $customer_id
 * @property \Illuminate\Support\Carbon $quote_date
 * @property \Illuminate\Support\Carbon|null $valid_until
 * @property string $status
 * @property int|null $created_by
 * @property string|null $notes
 */
class SalesQuotation extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'quote_number',
        'customer_id',
        'quote_date',
        'valid_until',
        'status',
        'created_by',
        'notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quote_date' => 'date',
            'valid_until' => 'date',
        ];
    }

    /**
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return HasMany<SalesQuotationLine, $this>
     */
    public function lines(): HasMany
    {
        return $this->hasMany(SalesQuotationLine::class);
    }
}
