<?php

namespace App\Services;

use App\Enums\CompetitionPublicationState;
use App\Enums\CompetitionStatus;
use App\Enums\SubmissionApprovalStatus;
use App\Models\Competition;
use App\Models\CompetitionCategoryJurorAssignment;
use App\Models\CompetitionSubmissionApproval;
use App\Models\JuryEvaluationSubmission;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

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

    /**
     * @param  array{status?: string|null, date_from?: string|null, date_to?: string|null, overdue?: bool}  $filters
     * @return Collection<int, Competition>
     */
    public function attentionQueue(array $filters = [], int $limit = 12): Collection
    {
        $query = Competition::query()
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
            });

        if (filled($filters['status'] ?? null)) {
            $query->where('status', $filters['status']);
        }
        if (filled($filters['date_from'] ?? null)) {
            $query->where('application_ends_at', '>=', Carbon::parse($filters['date_from'])->startOfDay());
        }
        if (filled($filters['date_to'] ?? null)) {
            $query->where('application_ends_at', '<=', Carbon::parse($filters['date_to'])->endOfDay());
        }

        return $query
            ->orderByRaw('CASE WHEN application_ends_at IS NULL THEN 1 ELSE 0 END')
            ->orderBy('application_ends_at')
            ->limit(max($limit * 10, 100))
            ->get()
            ->map(function (Competition $competition) {
                $expected = CompetitionCategoryJurorAssignment::query()
                    ->whereNotNull('juror_id')
                    ->whereHas('category', fn ($query) => $query->where('competition_id', $competition->id))
                    ->count();
                $roundId = $competition->evaluationRounds()->where('round_number', 1)->value('id');
                $completed = $roundId ? JuryEvaluationSubmission::where('competition_evaluation_round_id', $roundId)->count() : 0;
                $competition->setAttribute('jury_progress', ['completed' => $completed, 'expected' => $expected]);
                $deadline = $this->deadlineFor($competition);
                $competition->setAttribute('operation_deadline_at', $deadline);
                $competition->setAttribute('operation_overdue', $deadline?->isPast() ?? false);
                $competition->setAttribute('operation_delay_hours', $deadline?->isPast() ? $deadline->diffInHours(now()) : 0);

                return $competition;
            })
            ->when($filters['overdue'] ?? false, fn (Collection $competitions) => $competitions->where('operation_overdue', true))
            ->take($limit)
            ->values();
    }

    private function deadlineFor(Competition $competition): ?Carbon
    {
        if (in_array($competition->status, [CompetitionStatus::Submitted, CompetitionStatus::UnderReview, CompetitionStatus::WaitingRequirements], true)) {
            return $competition->submitted_at?->copy()->addHours((int) config('operations.sla.review_hours', 48));
        }

        if ($competition->status === CompetitionStatus::NeedsInfo) {
            return $competition->updated_at?->copy()->addHours((int) config('operations.sla.correction_hours', 72));
        }

        if ($competition->competition_ends_at?->isPast() && $competition->results_published_at === null) {
            return ($competition->evaluation_ends_at ?? $competition->competition_ends_at)?->copy();
        }

        if ($competition->pending_approval_count > 0 || $competition->pending_juror_count > 0) {
            return $competition->application_ends_at?->copy();
        }

        return $competition->application_ends_at?->copy();
    }
}
