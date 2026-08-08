<?php

namespace App\Services\Sms;

use App\Contracts\SmsSender;
use Illuminate\Support\Facades\Log;

/**
 * Mock SMS göndericisi: gerçek gönderim yapmaz, mesajı log'a yazar.
 * Geliştirme/test ortamı ve "SMS entegrasyonu hazır ama sağlayıcı yok" durumu için.
 * Gerçek sağlayıcı eklenince SMS_DRIVER=netgsm yapılır (bkz. NetgsmSmsSender).
 */
class LogSmsSender implements SmsSender
{
    public function __construct(private readonly string $from = 'TFSF') {}

    public function send(string $to, string $message): bool
    {
        Log::channel(config('logging.default'))->info('[SMS:mock] gönderildi', [
            'from' => $this->from,
            'to' => $this->normalize($to),
            'message' => $message,
        ]);

        return true;
    }

    private function normalize(string $to): string
    {
        $digits = preg_replace('/\D/', '', $to);

        return $digits ? '+'.ltrim($digits, '+') : $to;
    }
}
