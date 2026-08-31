<?php

namespace App\Listeners;

use App\Models\NotificationDeliveryLog;
use Illuminate\Notifications\Events\NotificationSent;

class LogNotificationSent
{
    public function handle(NotificationSent $event): void
    {
        NotificationDeliveryLog::create([
            'notification_id' => $event->notification->id ?? null,
            'notification_type' => $event->notification::class,
            'notifiable_id' => method_exists($event->notifiable, 'getKey') ? $event->notifiable->getKey() : null,
            'notifiable_type' => $event->notifiable::class,
            'channel' => $event->channel,
            'status' => 'sent',
            'response' => is_scalar($event->response) ? (string) $event->response : json_encode($event->response, JSON_UNESCAPED_UNICODE),
            'attempted_at' => now(),
        ]);
    }
}
