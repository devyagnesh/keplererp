<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Internal purchase request before PO.
 *
 * @property int $id
 * @property string $pr_number
 * @property \Illuminate\Support\Carbon|null $required_date
 * @property string $status
 * @property int $requested_by
 * @property string|null $notes
 * @property int|null $warehouse_id
 * @property int|null $approved_by
 * @property \Illuminate\Support\Carbon|null $approved_at
 * @property string|null $rejected_reason
 */
class PurchaseRequisition extends Model
{
    use SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'pr_number',
        'required_date',
        'status',
        'requested_by',
        'notes',
        'warehouse_id',
        'approved_by',
        'approved_at',
        'rejected_reason',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'required_date' => 'date',
            'approved_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Warehouse, $this>
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    /**
     * @return HasMany<PurchaseRequisitionLine, $this>
     */
    public function lines(): HasMany
    {
        return $this->hasMany(PurchaseRequisitionLine::class);
    }
}
