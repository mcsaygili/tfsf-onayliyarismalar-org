<?php

namespace App\Listeners;

use App\Models\MailSendLog;
use App\Models\NotificationDispatch;
use Illuminate\Mail\Events\MessageSent;

/**
 * Gönderilen her e-postayı mail_send_log'a kaydeder. Resend'in ürettiği
 * mesaj id'si, ResendTransport tarafından orijinal Symfony Email'e
 * X-Resend-Email-ID header'ı olarak eklendiği için burada okunabiliyor
 * (bkz. Illuminate\Mail\Transport\ResendTransport::doSend()).
 */
class LogSentMail
{
    public function handle(MessageSent $event): void
    {
        $to = collect($event->message->getTo())
            ->map(fn ($address) => $address->getAddress())
            ->implode(', ');

        $providerMessageId = $event->message->getHeaders()->get('X-Resend-Email-ID')?->getBodyAsString()
            ?: $event->sent->getMessageId();
        $dispatchId = $event->message->getHeaders()->get('X-TFSF-Dispatch-ID')?->getBodyAsString();
        $templateKey = $event->message->getHeaders()->get('X-TFSF-Template-Key')?->getBodyAsString();
        $competitionId = $event->message->getHeaders()->get('X-TFSF-Competition-ID')?->getBodyAsString();
        $dispatch = $dispatchId ? NotificationDispatch::query()->find($dispatchId) : null;

        MailSendLog::create([
            'notification_dispatch_id' => $dispatch?->id,
            'competition_id' => $competitionId ?: $dispatch?->competition_id,
            'mailable' => $event->data['__laravel_notification'] ?? $event->data['__laravel_mailable'] ?? $dispatch?->type,
            'to' => $to,
            'subject' => $event->message->getSubject(),
            'status' => 'sent',
            'provider' => 'resend',
            'locale' => $dispatch?->locale,
            'template_key' => $templateKey ?: $dispatch?->template_key,
            'attempt_number' => $dispatch?->attempts ?: 1,
            'provider_message_id' => $providerMessageId,
            'sent_at' => now(),
        ]);

        $dispatch?->forceFill([
            'status' => 'sent',
            'provider_message_id' => $providerMessageId,
            'sent_at' => now(),
            'last_error' => null,
        ])->save();
    }
}
