<?php

namespace App\Notifications\Juri;

use App\Models\Competition;
use App\Services\NotificationTemplateRenderer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CompetitionResultsPublishedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /** @param array<int, string> $channels */
    public function __construct(
        private readonly Competition $competition,
        private readonly array $channels = ['mail', 'database'],
        private readonly ?string $dispatchId = null,
        private readonly string $messageLocale = 'tr',
    ) {}

    public function via(object $notifiable): array
    {
        return $this->channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $competitionName = $this->competition->getTranslation($this->messageLocale)?->name ?: $this->competition->name;

        return app(NotificationTemplateRenderer::class)->mail(
            'competition_results_jury',
            $this->messageLocale,
            ['name' => $notifiable->first_name ?: $notifiable->email, 'competition' => $competitionName],
            route('juri.assignments.show', $this->competition),
            $this->dispatchId,
            $this->competition->id,
        );
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
