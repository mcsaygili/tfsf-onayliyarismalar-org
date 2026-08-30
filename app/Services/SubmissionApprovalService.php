<?php

namespace App\Services;

use App\Enums\CompetitionEntryStatus;
use App\Enums\CompetitionSubmissionStatus;
use App\Enums\SubmissionApprovalStatus;
use App\Models\CompetitionSubmissionApproval;
use App\Notifications\Uye\CompetitionSubmissionDecisionNotification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class SubmissionApprovalService
{
    public function decide(CompetitionSubmissionApproval $approval, Model $actor, bool $approved, ?string $note = null): void
    {
        DB::transaction(function () use ($approval, $actor, $approved, $note) {
            $approval = CompetitionSubmissionApproval::query()->whereKey($approval->id)->lockForUpdate()->firstOrFail();
            if ($approval->status !== SubmissionApprovalStatus::Pending) {
                return;
            }

            $approval->update([
                'status' => $approved ? SubmissionApprovalStatus::Approved : SubmissionApprovalStatus::Rejected,
                'reviewed_by_type' => $actor::class,
                'reviewed_by_id' => $actor->getKey(),
                'note' => $note,
                'reviewed_at' => now(),
            ]);

            $submission = $approval->submission;
            $submission->update($approved ? [
                'status' => CompetitionSubmissionStatus::Approved,
                'approved_at' => now(),
                'rejected_at' => null,
                'rejection_reason' => null,
            ] : [
                'status' => CompetitionSubmissionStatus::Rejected,
                'rejected_at' => now(),
                'rejection_reason' => $note,
            ]);

            $entry = $submission->entry;
            $statuses = $entry->submissions()->get(['status'])->pluck('status');
            if ($statuses->contains(CompetitionSubmissionStatus::PendingApproval)) {
                $entryStatus = CompetitionEntryStatus::PendingApproval;
            } elseif ($statuses->contains(CompetitionSubmissionStatus::Approved)) {
                $entryStatus = CompetitionEntryStatus::Approved;
            } else {
                $entryStatus = CompetitionEntryStatus::Rejected;
            }

            $entry->update([
                'status' => $entryStatus,
                'approved_at' => $entryStatus === CompetitionEntryStatus::Approved ? now() : null,
                'rejected_at' => $entryStatus === CompetitionEntryStatus::Rejected ? now() : null,
            ]);
            $entry->events()->create([
                'event' => $approved ? 'submission_approved' : 'submission_rejected',
                'actor_type' => $actor::class,
                'actor_id' => $actor->getKey(),
                'context' => ['submission_id' => $submission->id, 'note' => $note],
            ]);

            $entry->user->notify(new CompetitionSubmissionDecisionNotification($submission->loadMissing('category.translations', 'entry.competition.translations'), $approved, $note));
        });
    }
}
