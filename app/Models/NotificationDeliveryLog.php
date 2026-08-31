<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable(['notification_id', 'notification_type', 'notifiable_id', 'notifiable_type', 'channel', 'status', 'response', 'attempted_at'])]
class NotificationDeliveryLog extends Model
{
    use HasUuids;

    protected function casts(): array
    {
        return ['attempted_at' => 'datetime'];
    }

    public function notifiable(): MorphTo
    {
        return $this->morphTo();
    }
}
