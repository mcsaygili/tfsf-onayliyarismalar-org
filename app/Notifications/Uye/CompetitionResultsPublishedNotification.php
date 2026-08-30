<?php

namespace App\Notifications\Uye;

use App\Models\Competition;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CompetitionResultsPublishedNotification extends Notification
{
    public function __construct(private readonly Competition $competition) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('uye.competitions.notifications.results_subject'))
            ->greeting(__('uye.competitions.notifications.results_greeting', ['name' => $notifiable->first_name ?: $notifiable->email]))
            ->line(__('uye.competitions.notifications.results_line', ['competition' => $this->competition->name]))
            ->action(__('uye.competitions.notifications.results_action'), route('competitions.show', $this->competition));
    }
}
