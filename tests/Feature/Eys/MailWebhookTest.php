<?php

namespace Tests\Feature\Eys;

use App\Models\MailSendLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MailWebhookTest extends TestCase
{
    use RefreshDatabase;

    private const SECRET = 'MfKQ9r8GKYqrTwjUPD8ILPZIo2LaLaSw';

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.resend.webhook_secret' => self::SECRET]);
    }

    /** @return array{payload: string, headers: array<string, string>} */
    private function signedRequest(array $payload): array
    {
        $body = json_encode($payload);
        $id = 'msg_test123';
        $timestamp = (string) time();

        $toSign = "{$id}.{$timestamp}.{$body}";
        $signature = base64_encode(hash_hmac('sha256', $toSign, base64_decode(self::SECRET), true));

        return [
            'payload' => $body,
            'headers' => [
                'svix-id' => $id,
                'svix-timestamp' => $timestamp,
                'svix-signature' => "v1,{$signature}",
            ],
        ];
    }

    public function test_gecerli_imzali_webhook_kabul_edilir_ve_olay_kaydedilir(): void
    {
        $log = MailSendLog::create([
            'to' => 'alici@example.com',
            'subject' => 'Test',
            'provider_message_id' => 'resend-msg-123',
        ]);

        $request = $this->signedRequest([
            'type' => 'email.delivered',
            'data' => ['email_id' => 'resend-msg-123'],
        ]);

        $response = $this->call('POST', route('webhooks.resend'), [], [], [], $this->transformHeadersToServerVars($request['headers']), $request['payload']);

        $response->assertOk();
        $this->assertDatabaseHas('mail_events', [
            'mail_send_log_id' => $log->id,
            'event_type' => 'email.delivered',
        ]);
    }

    public function test_gecersiz_imzali_webhook_reddedilir(): void
    {
        $request = $this->signedRequest([
            'type' => 'email.delivered',
            'data' => ['email_id' => 'resend-msg-123'],
        ]);
        $request['headers']['svix-signature'] = 'v1,gecersiz-imza';

        $response = $this->call('POST', route('webhooks.resend'), [], [], [], $this->transformHeadersToServerVars($request['headers']), $request['payload']);

        $response->assertStatus(400);
        $this->assertDatabaseCount('mail_events', 0);
    }
}
