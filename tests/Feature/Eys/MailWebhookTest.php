<?php

namespace Tests\Feature\Eys;

use App\Models\MailSendLog;
use App\Models\NotificationDispatch;
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
    private function signedRequest(array $payload, string $id = 'msg_test123'): array
    {
        $body = json_encode($payload);
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

    public function test_basarisiz_teslimat_dispatch_ve_gonderim_kaydini_gunceller(): void
    {
        $dispatch = NotificationDispatch::create([
            'type' => 'jury_invitation', 'recipient_email' => 'alici@example.com', 'locale' => 'tr',
            'template_key' => 'jury_invitation', 'status' => 'sent', 'payload' => [],
            'provider_message_id' => 'resend-failed-123',
        ]);
        $log = MailSendLog::create([
            'notification_dispatch_id' => $dispatch->id,
            'to' => 'alici@example.com',
            'status' => 'sent',
            'provider_message_id' => 'resend-failed-123',
        ]);
        $request = $this->signedRequest([
            'type' => 'email.bounced',
            'data' => ['email_id' => 'resend-failed-123', 'bounce' => ['message' => 'Mailbox unavailable']],
        ]);

        $this->call('POST', route('webhooks.resend'), [], [], [], $this->transformHeadersToServerVars($request['headers']), $request['payload'])->assertOk();

        $this->assertSame('bounced', $log->fresh()->status);
        $this->assertSame('bounced', $dispatch->fresh()->status);
        $this->assertSame('Mailbox unavailable', $dispatch->fresh()->last_error);

        $this->call('POST', route('webhooks.resend'), [], [], [], $this->transformHeadersToServerVars($request['headers']), $request['payload'])->assertOk();
        $this->assertDatabaseCount('mail_events', 1);
    }

    private function sendEvent(string $id, string $type, ?string $time = null, string $messageId = 'ordering-message'): void
    {
        $request = $this->signedRequest(['type' => $type, 'created_at' => $time, 'data' => ['email_id' => $messageId]], $id);
        $this->call('POST', route('webhooks.resend'), [], [], [], $this->transformHeadersToServerVars($request['headers']), $request['payload'])->assertOk();
    }

    public function test_replayed_and_late_delay_events_cannot_regress_delivery(): void
    {
        $log = MailSendLog::create(['to' => 'test@example.test', 'provider_message_id' => 'ordering-message', 'status' => 'sent']);
        $this->sendEvent('delay', 'email.delivery_delayed');
        $this->sendEvent('delivered', 'email.delivered');
        $deliveredAt = $log->fresh()->delivered_at;
        $this->sendEvent('delay', 'email.delivery_delayed');
        $this->sendEvent('another-delay', 'email.delivery_delayed');
        $this->assertSame('delivered', $log->fresh()->status);
        $this->assertEquals($deliveredAt, $log->fresh()->delivered_at);
        $this->assertDatabaseCount('mail_events', 3);
    }

    public function test_older_terminal_event_is_recorded_without_replacing_newer_status(): void
    {
        $log = MailSendLog::create(['to' => 'test@example.test', 'provider_message_id' => 'ordering-message', 'status' => 'sent']);
        $this->sendEvent('delivered', 'email.delivered', '2026-09-05T08:00:00Z');
        $this->sendEvent('old-failure', 'email.failed', '2026-09-05T07:00:00Z');
        $this->assertSame('delivered', $log->fresh()->status);
        $this->sendEvent('complaint', 'email.complained', '2026-09-05T09:00:00Z');
        $this->assertSame('complained', $log->fresh()->status);
        $this->assertDatabaseCount('mail_events', 3);
    }

    public function test_previous_attempt_event_does_not_change_current_dispatch(): void
    {
        $dispatch = NotificationDispatch::create(['type' => 'jury_invitation', 'recipient_email' => 'test@example.test', 'locale' => 'tr',
            'template_key' => 'jury_invitation', 'status' => 'sent', 'payload' => [], 'provider_message_id' => 'new-attempt']);
        $log = MailSendLog::create(['notification_dispatch_id' => $dispatch->id, 'to' => 'test@example.test', 'provider_message_id' => 'ordering-message', 'status' => 'sent']);
        $this->sendEvent('old-attempt-failed', 'email.failed');
        $this->assertSame('failed', $log->fresh()->status);
        $this->assertSame('sent', $dispatch->fresh()->status);
    }

    public function test_orphan_event_can_be_attached_when_message_record_arrives(): void
    {
        $this->sendEvent('early-delivery', 'email.delivered');
        $log = MailSendLog::create(['to' => 'test@example.test', 'provider_message_id' => 'ordering-message', 'status' => 'sent']);
        $this->sendEvent('early-delivery', 'email.delivered');
        $this->assertSame('delivered', $log->fresh()->status);
        $this->assertDatabaseCount('mail_events', 1);
        $this->assertDatabaseHas('mail_events', ['provider_event_id' => 'early-delivery', 'mail_send_log_id' => $log->id]);
    }
}
