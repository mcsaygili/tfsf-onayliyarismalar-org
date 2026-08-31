<?php

namespace App\Listeners;

use App\Models\NotificationDeliveryLog;
use Illuminate\Notifications\Events\NotificationFailed;

class LogNotificationFailed
{
    public function handle(NotificationFailed $event): void
    {
        NotificationDeliveryLog::create([
            'notification_id' => $event->notification->id ?? null,
            'notification_type' => $event->notification::class,
            'notifiable_id' => method_exists($event->notifiable, 'getKey') ? $event->notifiable->getKey() : null,
            'notifiable_type' => $event->notifiable::class,
            'channel' => $event->channel,
            'status' => 'failed',
            'response' => json_encode($event->data, JSON_UNESCAPED_UNICODE),
            'attempted_at' => now(),
        ]);
    }
}
