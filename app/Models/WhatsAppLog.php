<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Outbound WhatsApp template delivery log (SRS §17.4).
 *
 * @property int $id
 * @property string $event_type
 * @property string|null $reference_type
 * @property int|null $reference_id
 * @property string|null $recipient_name
 * @property string $recipient_number
 * @property string $template_name
 * @property string|null $message_id
 * @property string $status
 * @property string|null $error_message
 * @property \Illuminate\Support\Carbon|null $sent_at
 */
class WhatsAppLog extends Model
{
    /**
     * Explicit table name — otherwise Laravel resolves "whats_app_logs" from class WhatsAppLog.
     *
     * @var string
     */
    protected $table = 'whatsapp_logs';

    public const STATUS_QUEUED = 'QUEUED';

    public const STATUS_SENT = 'SENT';

    public const STATUS_DELIVERED = 'DELIVERED';

    public const STATUS_READ = 'READ';

    public const STATUS_FAILED = 'FAILED';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'event_type',
        'reference_type',
        'reference_id',
        'recipient_name',
        'recipient_number',
        'template_name',
        'message_id',
        'status',
        'error_message',
        'sent_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
        ];
    }
}
