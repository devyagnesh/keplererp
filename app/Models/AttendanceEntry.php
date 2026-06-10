<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $employee_id
 * @property Carbon $work_date
 * @property string $status
 * @property Carbon|null $check_in_at
 * @property Carbon|null $check_out_at
 * @property string|null $check_in_latitude
 * @property string|null $check_in_longitude
 * @property string|null $check_out_latitude
 * @property string|null $check_out_longitude
 * @property string|null $check_in_accuracy_m
 * @property string|null $check_out_accuracy_m
 * @property string|null $check_in_address
 * @property string|null $check_out_address
 * @property string|null $check_in_altitude_m
 * @property string|null $check_out_altitude_m
 * @property array<string, mixed>|null $check_in_meta
 * @property array<string, mixed>|null $check_out_meta
 * @property string $source
 * @property int|null $marked_by_user_id
 */
class AttendanceEntry extends Model
{
    public const SOURCE_HR_MANUAL = 'hr_manual';

    public const SOURCE_SELF_SERVICE = 'self_service';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'employee_id',
        'work_date',
        'status',
        'check_in_at',
        'check_out_at',
        'check_in_latitude',
        'check_in_longitude',
        'check_out_latitude',
        'check_out_longitude',
        'check_in_accuracy_m',
        'check_out_accuracy_m',
        'check_in_address',
        'check_out_address',
        'check_in_altitude_m',
        'check_out_altitude_m',
        'check_in_meta',
        'check_out_meta',
        'source',
        'marked_by_user_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'work_date' => 'date',
            'check_in_at' => 'datetime',
            'check_out_at' => 'datetime',
            'check_in_latitude' => 'decimal:8',
            'check_in_longitude' => 'decimal:8',
            'check_out_latitude' => 'decimal:8',
            'check_out_longitude' => 'decimal:8',
            'check_in_accuracy_m' => 'decimal:3',
            'check_out_accuracy_m' => 'decimal:3',
            'check_in_altitude_m' => 'decimal:3',
            'check_out_altitude_m' => 'decimal:3',
            'check_in_meta' => 'array',
            'check_out_meta' => 'array',
        ];
    }

    /**
     * @return BelongsTo<Employee, $this>
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function markedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'marked_by_user_id');
    }

    public function hasCheckInCoordinates(): bool
    {
        return $this->check_in_latitude !== null && $this->check_in_longitude !== null;
    }

    public function hasCheckOutCoordinates(): bool
    {
        return $this->check_out_latitude !== null && $this->check_out_longitude !== null;
    }

    public function checkInLocationLabel(): string
    {
        return $this->locationLabel(
            $this->check_in_address,
            $this->check_in_latitude,
            $this->check_in_longitude,
            $this->check_in_accuracy_m
        );
    }

    public function checkOutLocationLabel(): string
    {
        return $this->locationLabel(
            $this->check_out_address,
            $this->check_out_latitude,
            $this->check_out_longitude,
            $this->check_out_accuracy_m
        );
    }

    public function checkInMapUrl(): ?string
    {
        return $this->mapUrl($this->check_in_latitude, $this->check_in_longitude);
    }

    public function checkOutMapUrl(): ?string
    {
        return $this->mapUrl($this->check_out_latitude, $this->check_out_longitude);
    }

    private function locationLabel(?string $address, ?string $lat, ?string $lng, ?string $accuracy): string
    {
        if ($address !== null && $address !== '') {
            $label = $address;
            if ($accuracy !== null) {
                $label .= sprintf(' (±%.0fm GPS)', (float) $accuracy);
            }

            return $label;
        }

        if ($lat === null || $lng === null) {
            return '—';
        }

        $label = sprintf('%.6f, %.6f', (float) $lat, (float) $lng);
        if ($accuracy !== null) {
            $label .= sprintf(' (±%.0fm)', (float) $accuracy);
        }

        return $label;
    }

    private function mapUrl(?string $lat, ?string $lng): ?string
    {
        if ($lat === null || $lng === null) {
            return null;
        }

        return sprintf(
            'https://www.openstreetmap.org/?mlat=%s&mlon=%s#map=18/%s/%s',
            $lat,
            $lng,
            $lat,
            $lng
        );
    }
}
