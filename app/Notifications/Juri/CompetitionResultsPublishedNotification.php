<?php

namespace App\Notifications\Juri;

use App\Models\Competition;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CompetitionResultsPublishedNotification extends Notification
{
    public function __construct(private readonly Competition $competition) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('juri.results.mail_subject'))
            ->greeting(__('juri.results.mail_greeting', ['name' => $notifiable->first_name ?: $notifiable->email]))
            ->line(__('juri.results.mail_line', ['competition' => $this->competition->name]))
            ->action(__('juri.results.mail_action'), route('juri.assignments.show', $this->competition));
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => __('juri.notifications.results_title'),
            'message' => __('juri.results.mail_line', ['competition' => $this->competition->name]),
            'route_name' => 'juri.assignments.show',
            'route_parameters' => [$this->competition->id],
            'competition_id' => $this->competition->id,
        ];
    }
}
