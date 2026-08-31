<?php

namespace App\Notifications\Juri;

use App\Models\CompetitionSubmission;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class EvaluationReopenedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly CompetitionSubmission $submission) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        $competition = $this->submission->entry->competition;

        return [
            'title' => __('juri.notifications.evaluation_reopened_title'),
            'message' => __('juri.notifications.evaluation_reopened', ['competition' => $competition->name, 'category' => $this->submission->category->name]),
            'route_name' => 'juri.evaluations.show',
            'route_parameters' => [$competition->id, $this->submission->competition_category_id],
            'competition_id' => $competition->id,
        ];
    }
}
