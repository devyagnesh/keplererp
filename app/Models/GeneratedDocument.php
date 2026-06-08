<?php

namespace App\Models;

use App\Enums\PdfDocumentType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Stored PDF artifact with signed download URL metadata (SRS §21.1).
 *
 * @property int $id
 * @property PdfDocumentType|string $document_type
 * @property string $documentable_type
 * @property int $documentable_id
 * @property string $module
 * @property string $file_path
 * @property string $download_name
 * @property array<string, mixed>|null $meta
 * @property string|null $meta_hash
 * @property int|null $generated_by
 * @property \Illuminate\Support\Carbon|null $expires_at
 * @property int|null $superseded_by
 * @property bool $is_active
 */
class GeneratedDocument extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'document_type',
        'documentable_type',
        'documentable_id',
        'module',
        'file_path',
        'download_name',
        'meta',
        'meta_hash',
        'generated_by',
        'expires_at',
        'superseded_by',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'document_type' => PdfDocumentType::class,
            'meta' => 'array',
            'expires_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function documentable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function generator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    /**
     * Whether the signed download URL is still valid.
     */
    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }
}
