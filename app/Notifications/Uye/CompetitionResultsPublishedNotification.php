<?php

namespace App\Notifications\Uye;

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
        if ($this->channels !== ['mail', 'database']) {
            return $this->channels;
        }

        return data_get($notifiable->preferences, 'results_email', true) ? $this->channels : ['database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $competitionName = $this->competition->getTranslation($this->messageLocale)?->name ?: $this->competition->name;

        return app(NotificationTemplateRenderer::class)->mail(
            'competition_results_member',
            $this->messageLocale,
            ['name' => $notifiable->first_name ?: $notifiable->email, 'competition' => $competitionName],
            route('competitions.show', $this->competition),
            $this->dispatchId,
            $this->competition->id,
        );
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => __('uye.notifications.results_title'),
            'message' => __('uye.competitions.notifications.results_line', ['competition' => $this->competition->name]),
            'route_name' => 'competitions.show',
            'route_parameters' => [$this->competition->id],
            'competition_id' => $this->competition->id,
        ];
    }
}
