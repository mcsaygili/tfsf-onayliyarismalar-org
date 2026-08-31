<?php

namespace App\Notifications\Juri;

use App\Models\Competition;
use App\Services\NotificationTemplateRenderer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EvaluationDeadlineReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /** @param array<int, string> $channels */
    public function __construct(
        private readonly Competition $competition,
        private readonly string $categoryId,
        private readonly string $deadline,
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
            'jury_evaluation_deadline',
            $this->messageLocale,
            ['name' => $notifiable->first_name ?: $notifiable->email, 'competition' => $competitionName, 'deadline' => $this->deadline],
            route('juri.evaluations.show', [$this->competition, $this->categoryId]),
            $this->dispatchId,
            $this->competition->id,
        );
    }

    public function toArray(object $notifiable): array
    {
        return ['title' => 'Değerlendirme süresi yaklaşıyor', 'message' => $this->competition->name.' · Son tarih '.$this->deadline, 'route_name' => 'juri.evaluations.show', 'route_parameters' => [$this->competition->id, $this->categoryId], 'competition_id' => $this->competition->id];
    }
}
