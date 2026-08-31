<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'notification_dispatch_id', 'competition_id', 'mailable', 'to', 'subject', 'status',
    'provider', 'locale', 'template_key', 'attempt_number', 'provider_message_id', 'error',
    'sent_at', 'delivered_at', 'failed_at',
])]
class MailSendLog extends Model
{
    use HasUuids;

    protected function casts(): array
    {
        return ['attempt_number' => 'integer', 'sent_at' => 'datetime', 'delivered_at' => 'datetime', 'failed_at' => 'datetime'];
    }

    public function dispatch(): BelongsTo
    {
        return $this->belongsTo(NotificationDispatch::class, 'notification_dispatch_id');
    }

    public function competition(): BelongsTo
    {
        return $this->belongsTo(Competition::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(MailEvent::class);
    }
}
