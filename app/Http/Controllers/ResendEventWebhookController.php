<?php

namespace App\Http\Controllers;

use App\Models\MailEvent;
use App\Models\MailSendLog;
use App\Models\NotificationDispatch;
use App\Services\Resend\SvixSignatureVerifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Svix\Exception\WebhookVerificationException;

/**
 * Resend'in gönderdiği webhook olayları (delivered/bounced/complained vb.)
 * — Svix imzalı. `routes/webhooks.php` üzerinden, subdomain'e bağlı
 * OLMAYAN tek route grubu (bkz. o dosyadaki açıklama).
 */
class ResendEventWebhookController extends Controller
{
    public function __invoke(Request $request, SvixSignatureVerifier $verifier): JsonResponse
    {
        try {
            $payload = $verifier->verify($request->getContent(), [
                'svix-id' => (string) $request->header('svix-id'),
                'svix-timestamp' => (string) $request->header('svix-timestamp'),
                'svix-signature' => (string) $request->header('svix-signature'),
            ]);
        } catch (WebhookVerificationException) {
            return response()->json(['message' => 'Geçersiz imza.'], 400);
        }

        $emailId = $payload['data']['email_id'] ?? null;

        $mailSendLog = $emailId
            ? MailSendLog::query()->where('provider_message_id', $emailId)->first()
            : null;

        $providerEventId = (string) $request->header('svix-id');
        $eventType = $payload['type'] ?? 'unknown';

        MailEvent::firstOrCreate(
            ['provider_event_id' => $providerEventId],
            ['mail_send_log_id' => $mailSendLog?->id, 'event_type' => $eventType, 'payload' => $payload],
        );

        if ($mailSendLog) {
            $status = match ($eventType) {
                'email.delivered' => 'delivered',
                'email.bounced' => 'bounced',
                'email.failed' => 'failed',
                'email.complained' => 'complained',
                'email.suppressed' => 'suppressed',
                'email.delivery_delayed' => 'delivery_delayed',
                default => null,
            };

            if ($status) {
                $now = now();
                $mailSendLog->forceFill([
                    'status' => $status,
                    'delivered_at' => $status === 'delivered' ? $now : $mailSendLog->delivered_at,
                    'failed_at' => in_array($status, ['failed', 'bounced', 'suppressed'], true) ? $now : $mailSendLog->failed_at,
                    'error' => data_get($payload, 'data.bounce.message') ?: data_get($payload, 'data.error.message') ?: $mailSendLog->error,
                ])->save();

                $dispatch = $mailSendLog->dispatch
                    ?? NotificationDispatch::query()->where('provider_message_id', $emailId)->latest()->first();
                $dispatch?->forceFill([
                    'status' => $status,
                    'delivered_at' => $status === 'delivered' ? $now : $dispatch->delivered_at,
                    'failed_at' => in_array($status, ['failed', 'bounced', 'suppressed'], true) ? $now : $dispatch->failed_at,
                    'last_error' => data_get($payload, 'data.bounce.message') ?: data_get($payload, 'data.error.message') ?: $dispatch->last_error,
                ])->save();
            }
        }

        return response()->json(['message' => 'ok']);
    }
}
