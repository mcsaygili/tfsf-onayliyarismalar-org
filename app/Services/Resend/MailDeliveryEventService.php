<?php

namespace App\Services\Resend;

use App\Models\MailEvent;
use App\Models\MailSendLog;
use App\Models\NotificationDispatch;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class MailDeliveryEventService
{
    public function record(string $providerEventId, array $payload): void
    {
        DB::transaction(function () use ($providerEventId, $payload): void {
            $emailId = data_get($payload, 'data.email_id');
            $log = is_string($emailId) ? MailSendLog::where('provider_message_id', $emailId)->first() : null;
            // Match sender lock order: dispatch before attempt log.
            $dispatch = $log?->notification_dispatch_id
                ? NotificationDispatch::whereKey($log->notification_dispatch_id)->lockForUpdate()->first()
                : ($log ? NotificationDispatch::where('provider_message_id', $emailId)->lockForUpdate()->first() : null);
            $log = $log ? MailSendLog::whereKey($log->id)->lockForUpdate()->first() : null;
            $eventType = $payload['type'] ?? 'unknown';
            $event = MailEvent::firstOrCreate(['provider_event_id' => $providerEventId], [
                'mail_send_log_id' => $log?->id, 'event_type' => $eventType, 'payload' => $payload,
            ]);

            if (! $event->wasRecentlyCreated) {
                if (! $log || $event->mail_send_log_id !== null) {
                    return;
                }
                // An event received before its send log can be reconciled on retry.
                $event->update(['mail_send_log_id' => $log->id]);
                $payload = $event->payload;
                $eventType = $event->event_type;
            }
            if (! $log) {
                return;
            }

            $status = match ($eventType) {
                'email.delivered' => 'delivered',
                'email.bounced' => 'bounced',
                'email.failed' => 'failed',
                'email.complained' => 'complained',
                'email.suppressed' => 'suppressed',
                'email.delivery_delayed' => 'delivery_delayed',
                default => null,
            };
            $eventTime = $this->eventTime($payload['created_at'] ?? null);
            if ($status === null || ! $this->canApply($log, $status, $eventTime)) {
                return;
            }
            $time = $eventTime ?? now();
            $failed = in_array($status, ['failed', 'bounced', 'suppressed'], true);
            $error = data_get($payload, 'data.bounce.message') ?: data_get($payload, 'data.error.message');
            $log->forceFill([
                'status' => $status,
                'provider_status_at' => $eventTime ?? $log->provider_status_at,
                'delivered_at' => $status === 'delivered' ? ($log->delivered_at ?? $time) : $log->delivered_at,
                'failed_at' => $failed ? ($log->failed_at ?? $time) : $log->failed_at,
                'error' => $error ?: $log->error,
            ])->save();

            // A historical attempt must not overwrite the state of a later retry.
            if ($dispatch && $dispatch->provider_message_id === $emailId) {
                $dispatch->forceFill([
                    'status' => $status,
                    'delivered_at' => $status === 'delivered' ? ($dispatch->delivered_at ?? $time) : $dispatch->delivered_at,
                    'failed_at' => $failed ? ($dispatch->failed_at ?? $time) : $dispatch->failed_at,
                    'last_error' => $error ?: $dispatch->last_error,
                ])->save();
            }
        }, 3);
    }

    private function canApply(MailSendLog $log, string $status, ?CarbonImmutable $time): bool
    {
        if ($time && $log->provider_status_at && $time->lt($log->provider_status_at)) {
            return false;
        }
        $terminal = ['delivered', 'bounced', 'failed', 'suppressed', 'complained'];
        if (in_array($log->status, $terminal, true)) {
            if ($status === 'delivery_delayed' || $log->status === 'complained') {
                return false;
            }
            // Without a strictly newer provider timestamp, retain the terminal
            // outcome. A complaint can follow delivery even without a timestamp.
            if ($status !== 'complained' && (! $time || ! $log->provider_status_at || ! $time->gt($log->provider_status_at))) {
                return false;
            }
        }

        return true;
    }

    private function eventTime(mixed $value): ?CarbonImmutable
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }
        try {
            return CarbonImmutable::parse($value)->utc();
        } catch (\Throwable) {
            return null;
        }
    }
}
