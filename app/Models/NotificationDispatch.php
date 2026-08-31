<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable([
    'competition_id', 'type', 'recipient_type', 'recipient_id', 'recipient_email', 'locale',
    'template_key', 'status', 'attempts', 'max_attempts', 'manual_retry_count', 'payload',
    'last_error', 'provider_message_id', 'scheduled_at', 'last_attempt_at', 'next_retry_at',
    'sent_at', 'delivered_at', 'failed_at', 'last_retried_by',
])]
class NotificationDispatch extends Model
{
    use HasUuids;

    public const RETRYABLE_STATUSES = ['failed', 'bounced', 'delivery_delayed', 'suppressed'];

    protected function casts(): array
    {
        return [
            'payload' => 'encrypted:array',
            'attempts' => 'integer',
            'max_attempts' => 'integer',
            'manual_retry_count' => 'integer',
            'scheduled_at' => 'datetime',
            'last_attempt_at' => 'datetime',
            'next_retry_at' => 'datetime',
            'sent_at' => 'datetime',
            'delivered_at' => 'datetime',
            'failed_at' => 'datetime',
        ];
    }

    public function competition(): BelongsTo
    {
        return $this->belongsTo(Competition::class);
    }

    public function recipient(): MorphTo
    {
        return $this->morphTo();
    }

    public function lastRetriedBy(): BelongsTo
    {
        return $this->belongsTo(EysUser::class, 'last_retried_by');
    }

    public function isRetryable(): bool
    {
        return in_array($this->status, self::RETRYABLE_STATUSES, true);
    }
}
