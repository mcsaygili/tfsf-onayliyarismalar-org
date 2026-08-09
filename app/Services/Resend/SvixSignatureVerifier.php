<?php

namespace App\Services\Resend;

use Svix\Exception\WebhookVerificationException;
use Svix\Webhook;

/**
 * Resend, webhook olaylarını Svix üzerinden imzalıyor. Bu sınıf sadece
 * `Svix\Webhook`'u sarmalıyor — imza geçersizse WebhookVerificationException
 * fırlatır, geçerliyse decode edilmiş payload dizisini döner.
 */
class SvixSignatureVerifier
{
    public function __construct(private readonly ?string $secret) {}

    /**
     * @param  array<string, string>  $headers
     * @return array<string, mixed>
     *
     * @throws WebhookVerificationException
     */
    public function verify(string $payload, array $headers): array
    {
        if (blank($this->secret)) {
            throw new WebhookVerificationException('Resend webhook secret yapılandırılmamış.');
        }

        return (new Webhook($this->secret))->verify($payload, $headers);
    }
}
