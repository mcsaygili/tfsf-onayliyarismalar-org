<?php

namespace App\Services;

use App\Enums\CompetitionEntryStatus;
use App\Enums\CompetitionInfrastructureProvider;
use App\Enums\CompetitionSubmissionStatus;
use App\Enums\SubmissionApprovalStatus;
use App\Models\Competition;
use App\Models\CompetitionEntry;
use App\Models\CompetitionSubmission;
use App\Models\User;
use App\Support\Photo\SubmissionDeclarations;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CompetitionEntryService
{
    public function __construct(private readonly MemberEligibilityService $eligibility) {}

    public function entryFor(Competition $competition, User $user): CompetitionEntry
    {
        if ($competition->infrastructure_provider !== CompetitionInfrastructureProvider::Tfsf) {
            throw ValidationException::withMessages(['competition' => __('uye.competitions.errors.external_competition')]);
        }

        $check = $this->eligibility->forCompetition($competition, $user);
        if (! $check['eligible']) {
            throw ValidationException::withMessages([
                'competition' => collect($check['violations'])
                    ->map(fn ($code) => __('uye.competitions.violations.'.$code))
                    ->join(' '),
            ]);
        }

        return CompetitionEntry::firstOrCreate(
            ['competition_id' => $competition->id, 'user_id' => $user->id],
            ['status' => CompetitionEntryStatus::Draft, 'eligibility_snapshot' => $check],
        );
    }

    public function addCategory(CompetitionEntry $entry, string $categoryId): CompetitionSubmission
    {
        return DB::transaction(function () use ($entry, $categoryId) {
            CompetitionMutationLock::acquire($entry->competition_id);
            $entry = CompetitionEntry::whereKey($entry->id)->lockForUpdate()->firstOrFail();
            if (! $entry->status->isEditable()) {
                throw ValidationException::withMessages(['category' => __('uye.competitions.errors.entry_locked')]);
            }
            $category = $entry->competition->categories()->whereKey($categoryId)->firstOrFail();
            $check = $this->eligibility->forCategory($category, $entry->user);
            if (! $check['eligible']) {
                throw ValidationException::withMessages(['category' => collect($check['violations'])->map(fn ($code) => __('uye.competitions.violations.'.$code))->join(' ')]);
            }

            return $entry->submissions()->firstOrCreate(
                ['competition_category_id' => $category->id],
                ['status' => CompetitionSubmissionStatus::Draft, 'eligibility_snapshot' => $check],
            );
        });
    }

    public function submit(CompetitionEntry $entry): CompetitionEntry
    {
        return DB::transaction(function () use ($entry) {
            CompetitionMutationLock::acquire($entry->competition_id);
            $entry = CompetitionEntry::query()->whereKey($entry->id)->lockForUpdate()->firstOrFail();
            $entry->load(['competition.participantApprovalProcess', 'competition.regulationSnapshots', 'user', 'submissions.category', 'submissions.activePhotos']);
            if (! $entry->status->isEditable()) {
                throw ValidationException::withMessages(['entry' => __('uye.competitions.errors.entry_locked')]);
            }
            if ($entry->submissions->isEmpty()) {
                throw ValidationException::withMessages(['entry' => __('uye.competitions.errors.category_required')]);
            }

            $competitionCheck = $this->eligibility->forCompetition($entry->competition, $entry->user);
            if (! $competitionCheck['eligible']) {
                throw ValidationException::withMessages(['entry' => __('uye.competitions.errors.eligibility_changed')]);
            }

            foreach ($entry->submissions as $submission) {
                $check = $this->eligibility->forCategory($submission->category, $entry->user);
                if (! $check['eligible']) {
                    throw ValidationException::withMessages(['entry' => __('uye.competitions.errors.eligibility_changed')]);
                }
                if ($submission->activePhotos->isEmpty()) {
                    throw ValidationException::withMessages(['entry' => __('uye.competitions.errors.photo_required', ['category' => $submission->category->name])]);
                }
                if ($submission->activePhotos->count() > $submission->category->max_photos_per_participant) {
                    throw ValidationException::withMessages(['entry' => __('uye.competitions.errors.photo_limit')]);
                }

                SubmissionDeclarations::assertComplete($submission);

                $processCode = $entry->competition->participantApprovalProcess?->code;
                $submissionStatus = $processCode ? CompetitionSubmissionStatus::PendingApproval : CompetitionSubmissionStatus::Approved;
                $submission->update([
                    'status' => $submissionStatus,
                    'eligibility_snapshot' => $check,
                    'submitted_at' => now(),
                    'approved_at' => $processCode ? null : now(),
                ]);

                if ($processCode) {
                    $submission->approvals()->updateOrCreate(
                        ['approval_type' => $processCode],
                        ['status' => SubmissionApprovalStatus::Pending, 'sequence' => 1],
                    );
                }
            }

            $pending = $entry->submissions->contains(fn ($submission) => $submission->fresh()->status === CompetitionSubmissionStatus::PendingApproval);
            $entry->update([
                'status' => $pending ? CompetitionEntryStatus::PendingApproval : CompetitionEntryStatus::Approved,
                'eligibility_snapshot' => $competitionCheck,
                'regulation_snapshot_id' => $entry->competition->regulationSnapshots->first()?->id,
                'consent_at' => now(),
                'submitted_at' => now(),
                'approved_at' => $pending ? null : now(),
            ]);
            $entry->events()->create(['event' => 'submitted', 'actor_type' => $entry->user::class, 'actor_id' => $entry->user_id]);

            return $entry->fresh(['submissions.photos', 'submissions.category']);
        });
    }
}
