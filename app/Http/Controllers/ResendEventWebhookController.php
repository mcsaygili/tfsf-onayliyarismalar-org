<?php

namespace App\Http\Controllers;

use App\Models\MailEvent;
use App\Models\MailSendLog;
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

        MailEvent::create([
            'mail_send_log_id' => $mailSendLog?->id,
            'event_type' => $payload['type'] ?? 'unknown',
            'payload' => $payload,
        ]);

        return response()->json(['message' => 'ok']);
    }
}
