<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['mailable', 'to', 'subject', 'status', 'provider', 'provider_message_id', 'error'])]
class MailSendLog extends Model
{
    use HasUuids;

    public function events(): HasMany
    {
        return $this->hasMany(MailEvent::class);
    }
}
