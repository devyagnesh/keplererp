<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Request;

/**
 * Persists critical ERP audit events without external packages.
 */
class AuditLogService
{
    /**
     * @param  array<string, mixed>|null  $properties
     */
    public function record(
        string $action,
        string $description,
        ?Model $subject = null,
        ?User $actor = null,
        ?array $properties = null
    ): AuditLog {
        return AuditLog::query()->create([
            'action' => $action,
            'subject_type' => $subject !== null ? $subject::class : null,
            'subject_id' => $subject?->getKey(),
            'description' => $description,
            'properties' => $properties,
            'user_id' => $actor?->id,
            'ip_address' => Request::ip(),
        ]);
    }
}
