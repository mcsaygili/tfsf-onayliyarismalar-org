<?php

namespace App\Notifications\Juri;

use App\Models\Competition;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EvaluationDeadlineReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly Competition $competition, private readonly string $categoryId, private readonly string $deadline) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)->subject('Jüri değerlendirme süresi yaklaşıyor')
            ->greeting('Merhaba '.($notifiable->first_name ?: $notifiable->email).',')
            ->line($this->competition->name.' yarışmasındaki jüri değerlendirmeniz henüz tamamlanmadı.')
            ->line('Son tarih: '.$this->deadline)
            ->action('Değerlendirmeye git', route('juri.evaluations.show', [$this->competition, $this->categoryId]));
    }

    public function toArray(object $notifiable): array
    {
        return ['title' => 'Değerlendirme süresi yaklaşıyor', 'message' => $this->competition->name.' · Son tarih '.$this->deadline, 'route_name' => 'juri.evaluations.show', 'route_parameters' => [$this->competition->id, $this->categoryId], 'competition_id' => $this->competition->id];
    }
}
