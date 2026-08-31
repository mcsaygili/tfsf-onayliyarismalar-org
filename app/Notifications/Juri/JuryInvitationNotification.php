<?php

namespace App\Notifications\Juri;

use App\Models\JuryInvitation;
use App\Services\NotificationTemplateRenderer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class JuryInvitationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly JuryInvitation $invitation,
        private readonly string $plainToken,
        private readonly ?string $dispatchId = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $locale = $this->invitation->locale;
        $competitionName = $this->invitation->competition?->getTranslation($locale)?->name
            ?: $this->invitation->competition?->name
            ?: trans('juri.invitation.unnamed_competition', [], $locale);

        return app(NotificationTemplateRenderer::class)->mail(
            'jury_invitation',
            $locale,
            [
                'name' => $this->invitation->first_name,
                'institution' => $this->invitation->institution?->name,
                'competition' => $competitionName,
                'expiry' => $this->invitation->expires_at?->timezone(config('app.timezone'))->format('d.m.Y H:i'),
            ],
            route('juri.invitation.accept', $this->plainToken),
            $this->dispatchId,
            $this->invitation->competition_id,
        );
    }
}
