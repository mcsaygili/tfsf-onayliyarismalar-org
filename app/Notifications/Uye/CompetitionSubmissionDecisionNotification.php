<?php

namespace App\Notifications\Uye;

use App\Models\CompetitionSubmission;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class CompetitionSubmissionDecisionNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly CompetitionSubmission $submission, private readonly bool $approved, private readonly ?string $note = null) {}

    public function via(object $notifiable): array
    {
        return data_get($notifiable->preferences, 'submission_database', true) ? ['database'] : [];
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        $competition = $this->submission->entry->competition;

        return [
            'title' => __('uye.notifications.submission_decision_title'),
            'message' => $this->approved
                ? __('uye.notifications.submission_approved', ['competition' => $competition->name, 'category' => $this->submission->category->name])
                : __('uye.notifications.submission_rejected', ['competition' => $competition->name, 'category' => $this->submission->category->name, 'note' => $this->note]),
            'route_name' => 'competitions.entry.show',
            'route_parameters' => [$this->submission->competition_entry_id],
            'competition_id' => $competition->id,
        ];
    }
}
