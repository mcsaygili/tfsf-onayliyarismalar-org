<?php

namespace App\Listeners;

use App\Models\MailSendLog;
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

        $providerMessageId = $event->message->getHeaders()->get('X-Resend-Email-ID')?->getBodyAsString();

        MailSendLog::create([
            'mailable' => $event->data['__laravel_mailable'] ?? null,
            'to' => $to,
            'subject' => $event->message->getSubject(),
            'status' => 'sent',
            'provider' => 'resend',
            'provider_message_id' => $providerMessageId,
        ]);
    }
}
