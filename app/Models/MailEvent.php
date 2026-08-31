<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['provider_event_id', 'mail_send_log_id', 'event_type', 'payload'])]
class MailEvent extends Model
{
    use HasUuids;

    protected function casts(): array
    {
        return [
            'payload' => 'array',
        ];
    }

    public function mailSendLog(): BelongsTo
    {
        return $this->belongsTo(MailSendLog::class);
    }
}
