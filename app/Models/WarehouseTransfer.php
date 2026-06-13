<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * Inter-warehouse stock transfer document (SRS UC 22.7).
 *
 * @property int $id
 * @property string $transfer_number
 * @property int $from_warehouse_id
 * @property int $to_warehouse_id
 * @property string $status
 * @property string|null $reason
 * @property string|null $vehicle_no
 * @property string|null $lr_number
 * @property Carbon|null $approved_at
 * @property Carbon|null $dispatched_at
 * @property Carbon|null $received_at
 */
class WarehouseTransfer extends Model
{
    use SoftDeletes;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_IN_TRANSIT = 'in_transit';

    public const STATUS_RECEIVED = 'received';

    public const STATUS_CANCELLED = 'cancelled';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'transfer_number',
        'from_warehouse_id',
        'to_warehouse_id',
        'status',
        'reason',
        'vehicle_no',
        'lr_number',
        'created_by',
        'approved_by',
        'approved_at',
        'dispatched_at',
        'received_at',
        'received_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'approved_at' => 'datetime',
            'dispatched_at' => 'datetime',
            'received_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Warehouse, $this>
     */
    public function fromWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'from_warehouse_id');
    }

    /**
     * @return BelongsTo<Warehouse, $this>
     */
    public function toWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'to_warehouse_id');
    }

    /**
     * @return HasMany<WarehouseTransferLine, $this>
     */
    public function lines(): HasMany
    {
        return $this->hasMany(WarehouseTransferLine::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
