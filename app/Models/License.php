<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * On-premise license record (SRS §18).
 *
 * @property int $id
 * @property string $license_key
 * @property string $client_name
 * @property string $domain
 * @property string|null $server_fingerprint
 * @property string|null $token
 * @property array<string, mixed>|null $modules_enabled
 * @property \Illuminate\Support\Carbon|null $issued_at
 * @property \Illuminate\Support\Carbon $expires_at
 * @property bool $is_active
 */
class License extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'license_key',
        'client_name',
        'domain',
        'server_fingerprint',
        'token',
        'modules_enabled',
        'issued_at',
        'expires_at',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'modules_enabled' => 'array',
            'issued_at' => 'date',
            'expires_at' => 'date',
            'is_active' => 'boolean',
        ];
    }
}
