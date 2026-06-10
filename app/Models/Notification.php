<?php

namespace App\Models;

use Database\Factories\NotificationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    /** @use HasFactory<NotificationFactory> */
    use HasFactory;

    public const CHANNEL_EMAIL = 'email';

    public const CHANNEL_SMS = 'sms';

    public const PRIORITY_CRITICAL = 'critical';

    public const PRIORITY_DEFAULT = 'default';

    public const PRIORITY_MARKETING = 'marketing';

    public const STATUS_QUEUED = 'queued';

    public const STATUS_SENT = 'sent';

    public const STATUS_DELIVERED = 'delivered';

    public const STATUS_DISCARDED = 'discarded';

    protected $fillable = [
        'subscriber_id',
        'batch_id',
        'idempotency_key',
        'channel',
        'content',
        'priority',
        'status',
        'sent_at',
        'delivered_at',
        'external_id',
        'failure_reason',
    ];

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
            'delivered_at' => 'datetime',
        ];
    }

    public function subscriber(): BelongsTo
    {
        return $this->belongsTo(Subscriber::class);
    }
}
