<?php

namespace App\Services;

use App\Enums\CompetitionPublicationState;
use App\Enums\CompetitionStatus;
use App\Enums\SubmissionApprovalStatus;
use App\Models\Competition;
use App\Models\CompetitionCategoryJurorAssignment;
use App\Models\CompetitionSubmissionApproval;
use App\Models\JuryEvaluationSubmission;

class CompetitionOperationsService
{
    /** @return array<string, int> */
    public function metrics(): array
    {
        return [
            'submitted' => Competition::whereIn('status', [CompetitionStatus::Submitted, CompetitionStatus::UnderReview, CompetitionStatus::WaitingRequirements])->count(),
            'pending_approvals' => CompetitionSubmissionApproval::where('status', SubmissionApprovalStatus::Pending)->count(),
            'pending_jurors' => CompetitionCategoryJurorAssignment::whereNull('juror_id')->count(),
            'evaluation_open' => Competition::where('evaluation_starts_at', '<=', now())->where('evaluation_ends_at', '>=', now())->count(),
            'published' => Competition::where('publication_state', CompetitionPublicationState::Published)->count(),
            'results_waiting' => Competition::where('status', CompetitionStatus::Approved)
                ->where('competition_ends_at', '<', now())->whereNull('results_published_at')->count(),
        ];
    }

    public function attentionQueue(int $limit = 12)
    {
        return Competition::query()
            ->with(['translations', 'institution', 'representative'])
            ->withCount([
                'categories',
                'entries as submitted_entry_count' => fn ($query) => $query->whereNotNull('submitted_at'),
                'categories as pending_juror_count' => fn ($query) => $query->whereHas('jurorAssignments', fn ($assignments) => $assignments->whereNull('juror_id')),
                'entries as pending_approval_count' => fn ($query) => $query->whereHas('submissions.approvals', fn ($approvals) => $approvals->where('status', SubmissionApprovalStatus::Pending)),
            ])
            ->where(function ($query) {
                $query->whereIn('status', [CompetitionStatus::Submitted, CompetitionStatus::UnderReview, CompetitionStatus::WaitingRequirements, CompetitionStatus::NeedsInfo])
                    ->orWhereHas('categories.jurorAssignments', fn ($assignments) => $assignments->whereNull('juror_id'))
                    ->orWhereHas('entries.submissions.approvals', fn ($approvals) => $approvals->where('status', SubmissionApprovalStatus::Pending))
                    ->orWhere(fn ($results) => $results->where('competition_ends_at', '<', now())->whereNull('results_published_at'));
            })
            ->orderByRaw('CASE WHEN application_ends_at IS NULL THEN 1 ELSE 0 END')
            ->orderBy('application_ends_at')
            ->limit($limit)
            ->get()
            ->map(function (Competition $competition) {
                $expected = CompetitionCategoryJurorAssignment::query()
                    ->whereNotNull('juror_id')
                    ->whereHas('category', fn ($query) => $query->where('competition_id', $competition->id))
                    ->count();
                $roundId = $competition->evaluationRounds()->where('round_number', 1)->value('id');
                $completed = $roundId ? JuryEvaluationSubmission::where('competition_evaluation_round_id', $roundId)->count() : 0;
                $competition->setAttribute('jury_progress', ['completed' => $completed, 'expected' => $expected]);

                return $competition;
            });
    }
}
