<?php

namespace App\Notifications\Juri;

use App\Models\JuryInvitation;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class JuryInvitationNotification extends Notification
{
    public function __construct(
        private readonly JuryInvitation $invitation,
        private readonly string $plainToken,
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

        return (new MailMessage)
            ->subject(trans('juri.invitation.mail_subject', [], $locale))
            ->greeting(trans('juri.invitation.mail_greeting', ['name' => $this->invitation->first_name], $locale))
            ->line(trans('juri.invitation.mail_line', [
                'institution' => $this->invitation->institution?->name,
                'competition' => $competitionName,
            ], $locale))
            ->action(
                trans('juri.invitation.mail_action', [], $locale),
                route('juri.invitation.accept', $this->plainToken)
            )
            ->line(trans('juri.invitation.mail_expiry', [
                'date' => $this->invitation->expires_at?->timezone(config('app.timezone'))->format('d.m.Y H:i'),
            ], $locale))
            ->line(trans('juri.invitation.mail_ignore', [], $locale));
    }
}
