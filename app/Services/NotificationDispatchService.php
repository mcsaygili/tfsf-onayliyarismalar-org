<?php

namespace App\Services;

use App\Jobs\SendNotificationDispatchJob;
use App\Models\Competition;
use App\Models\JuryInvitation;
use App\Models\MailSendLog;
use App\Models\MailSetting;
use App\Models\NotificationDispatch;
use App\Notifications\Juri\CompetitionResultsPublishedNotification as JuryResultsNotification;
use App\Notifications\Juri\EvaluationDeadlineReminderNotification;
use App\Notifications\Juri\JuryInvitationNotification;
use App\Notifications\Uye\CompetitionResultsPublishedNotification as MemberResultsNotification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

class NotificationDispatchService
{
    public function queueJuryInvitation(JuryInvitation $invitation, string $plainToken): NotificationDispatch
    {
        return $this->create([
            'competition_id' => $invitation->competition_id,
            'type' => 'jury_invitation',
            'recipient_email' => $invitation->email,
            'locale' => $invitation->locale ?: 'tr',
            'template_key' => 'jury_invitation',
            'payload' => ['invitation_id' => $invitation->id, 'plain_token' => $plainToken],
        ]);
    }

    public function queueEvaluationDeadline(Model $juror, Competition $competition, string $categoryId, string $deadline): NotificationDispatch
    {
        return $this->create([
            'competition_id' => $competition->id,
            'type' => 'jury_evaluation_deadline',
            'recipient_type' => $juror::class,
            'recipient_id' => $juror->getKey(),
            'recipient_email' => $juror->email,
            'locale' => $this->localeFor($juror),
            'template_key' => 'jury_evaluation_deadline',
            'payload' => ['category_id' => $categoryId, 'deadline' => $deadline],
        ]);
    }

    public function queueResults(Model $recipient, Competition $competition, bool $jury): NotificationDispatch
    {
        return $this->create([
            'competition_id' => $competition->id,
            'type' => $jury ? 'competition_results_jury' : 'competition_results_member',
            'recipient_type' => $recipient::class,
            'recipient_id' => $recipient->getKey(),
            'recipient_email' => $recipient->email,
            'locale' => $this->localeFor($recipient),
            'template_key' => $jury ? 'competition_results_jury' : 'competition_results_member',
            'payload' => [],
        ]);
    }

    /** @param array<string, mixed> $attributes */
    public function create(array $attributes): NotificationDispatch
    {
        $dispatch = NotificationDispatch::create(array_merge([
            'status' => 'pending',
            'scheduled_at' => now(),
            'max_attempts' => 3,
        ], $attributes));

        SendNotificationDispatchJob::dispatch($dispatch->id);

        return $dispatch;
    }

    public function retry(NotificationDispatch $dispatch, ?string $eysUserId = null): void
    {
        if (! $dispatch->isRetryable()) {
            throw new RuntimeException('Bu bildirim tekrar gönderilemez.');
        }

        $dispatch->forceFill([
            'status' => 'pending',
            'last_error' => null,
            'failed_at' => null,
            'next_retry_at' => null,
            'max_attempts' => max($dispatch->max_attempts, $dispatch->attempts + 3),
            'manual_retry_count' => $dispatch->manual_retry_count + 1,
            'last_retried_by' => $eysUserId,
        ])->save();

        SendNotificationDispatchJob::dispatch($dispatch->id);
    }

    public function sendAttempt(NotificationDispatch $dispatch): bool
    {
        $attempt = DB::transaction(function () use ($dispatch): int {
            $locked = NotificationDispatch::query()->lockForUpdate()->findOrFail($dispatch->id);
            $attempt = $locked->attempts + 1;
            $locked->forceFill([
                'status' => 'processing',
                'attempts' => $attempt,
                'last_attempt_at' => now(),
                'next_retry_at' => null,
            ])->save();
            $dispatch->refresh();

            return $attempt;
        });

        try {
            $this->assertSendingEnabled();
            $notification = $this->notificationFor($dispatch);
            $recipient = $dispatch->recipient;

            if ($recipient) {
                $recipient->notifyNow($notification, ['mail']);
            } elseif ($dispatch->recipient_email) {
                \Illuminate\Support\Facades\Notification::route('mail', $dispatch->recipient_email)
                    ->notifyNow($notification);
            } else {
                throw new RuntimeException('Bildirim alıcısı bulunamadı.');
            }

            $dispatch->refresh();
            if ($dispatch->status === 'processing') {
                $dispatch->forceFill(['status' => 'sent', 'sent_at' => now(), 'last_error' => null])->save();
            }

            return true;
        } catch (Throwable $exception) {
            $delay = $this->retryDelaySeconds($attempt);
            $willRetry = $attempt < $dispatch->max_attempts;
            $dispatch->forceFill([
                'status' => 'failed',
                'last_error' => mb_substr($exception->getMessage(), 0, 5000),
                'failed_at' => now(),
                'next_retry_at' => $willRetry ? now()->addSeconds($delay) : null,
            ])->save();

            MailSendLog::create([
                'notification_dispatch_id' => $dispatch->id,
                'competition_id' => $dispatch->competition_id,
                'mailable' => $dispatch->type,
                'to' => $dispatch->recipient_email ?: '—',
                'status' => 'failed',
                'provider' => 'resend',
                'locale' => $dispatch->locale,
                'template_key' => $dispatch->template_key,
                'attempt_number' => $attempt,
                'error' => mb_substr($exception->getMessage(), 0, 5000),
                'failed_at' => now(),
            ]);

            report($exception);

            return false;
        }
    }

    public function retryDelaySeconds(int $attempt): int
    {
        return [60, 300, 900, 1800][$attempt - 1] ?? 3600;
    }

    private function notificationFor(NotificationDispatch $dispatch): Notification
    {
        $competition = $dispatch->competition?->loadMissing('translations');
        $payload = $dispatch->payload ?? [];

        return match ($dispatch->type) {
            'jury_invitation' => new JuryInvitationNotification(
                JuryInvitation::query()->with(['competition.translations', 'institution'])->findOrFail($payload['invitation_id']),
                $payload['plain_token'],
                $dispatch->id,
            ),
            'jury_evaluation_deadline' => new EvaluationDeadlineReminderNotification(
                $competition ?? throw new RuntimeException('Yarışma bulunamadı.'),
                $payload['category_id'],
                $payload['deadline'],
                ['mail'],
                $dispatch->id,
                $dispatch->locale,
            ),
            'competition_results_jury' => new JuryResultsNotification(
                $competition ?? throw new RuntimeException('Yarışma bulunamadı.'),
                ['mail'],
                $dispatch->id,
                $dispatch->locale,
            ),
            'competition_results_member' => new MemberResultsNotification(
                $competition ?? throw new RuntimeException('Yarışma bulunamadı.'),
                ['mail'],
                $dispatch->id,
                $dispatch->locale,
            ),
            default => throw new RuntimeException("Bilinmeyen bildirim türü: {$dispatch->type}"),
        };
    }

    private function assertSendingEnabled(): void
    {
        $settings = MailSetting::current();
        if (! $settings->enabled) {
            throw new RuntimeException('Mail istemcisi EYS ayarlarından kapatılmış.');
        }

        if ($settings->daily_quota && MailSendLog::query()->whereDate('created_at', today())->where('status', '!=', 'failed')->count() >= $settings->daily_quota) {
            throw new RuntimeException('Günlük e-posta gönderim kotası doldu.');
        }
    }

    private function localeFor(Model $recipient): string
    {
        $locale = data_get($recipient, 'preferences.locale') ?: data_get($recipient, 'preferences.language') ?: 'tr';

        return in_array($locale, ['tr', 'en'], true) ? $locale : 'tr';
    }
}
