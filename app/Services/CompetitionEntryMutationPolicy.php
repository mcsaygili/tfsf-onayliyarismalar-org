<?php

namespace App\Services;

use App\Enums\CompetitionOperationalPhase;
use App\Enums\CompetitionSubmissionStatus;
use App\Models\CompetitionSubmission;
use App\Models\CompetitionSubmissionPhoto;

class CompetitionEntryMutationPolicy
{
    public function __construct(private readonly CompetitionPhaseService $phases) {}

    public function allows(CompetitionSubmission $submission): bool
    {
        $submission->loadMissing('entry.competition');
        $competition = $submission->entry->competition;

        if ($submission->status->isEditable() && $this->phases->acceptsApplications($competition)) {
            return true;
        }

        if ($submission->status !== CompetitionSubmissionStatus::Approved
            || $this->phases->phase($competition) !== CompetitionOperationalPhase::EvaluationOpen
            || ! $competition->competition_ends_at
            || $competition->competition_ends_at->isPast()
            || $competition->results_published_at) {
            return false;
        }

        $rounds = $competition->evaluationRounds()->get();

        return ! $rounds->contains(fn ($round) => $round->is_final || $round->round_number > 1);
    }

    public function allowsPhoto(CompetitionSubmissionPhoto $photo): bool
    {
        return $this->allows($photo->submission);
    }
}
