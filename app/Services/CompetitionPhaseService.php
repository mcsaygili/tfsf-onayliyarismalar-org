<?php

namespace App\Services;

use App\Enums\CompetitionOperationalPhase;
use App\Enums\CompetitionPublicationState;
use App\Enums\CompetitionStatus;
use App\Models\Competition;
use Illuminate\Support\Carbon;

class CompetitionPhaseService
{
    public function phase(Competition $competition, ?Carbon $now = null): CompetitionOperationalPhase
    {
        $now ??= now();

        if ($competition->publication_state === CompetitionPublicationState::Cancelled) {
            return CompetitionOperationalPhase::Cancelled;
        }
        if ($competition->publication_state === CompetitionPublicationState::Suspended) {
            return CompetitionOperationalPhase::Suspended;
        }
        if ($competition->status !== CompetitionStatus::Approved
            || $competition->publication_state !== CompetitionPublicationState::Published
            || ! $competition->published_at) {
            return CompetitionOperationalPhase::Unavailable;
        }
        if ($competition->results_published_at && $competition->results_published_at->lte($now)) {
            return CompetitionOperationalPhase::ResultsPublished;
        }
        if ($competition->evaluation_ends_at && $competition->evaluation_ends_at->lt($now)) {
            return CompetitionOperationalPhase::EvaluationClosed;
        }
        if ($competition->evaluation_starts_at && $competition->evaluation_starts_at->lte($now)) {
            return CompetitionOperationalPhase::EvaluationOpen;
        }
        if ($competition->application_ends_at && $competition->application_ends_at->lt($now)) {
            return $competition->participant_approval_process_id
                ? CompetitionOperationalPhase::ParticipantApproval
                : CompetitionOperationalPhase::ApplicationsClosed;
        }
        if ($competition->application_starts_at && $competition->application_starts_at->lte($now)
            && $competition->application_ends_at && $competition->application_ends_at->gte($now)) {
            return CompetitionOperationalPhase::ApplicationsOpen;
        }

        return CompetitionOperationalPhase::Scheduled;
    }

    public function acceptsApplications(Competition $competition): bool
    {
        return $this->phase($competition) === CompetitionOperationalPhase::ApplicationsOpen;
    }
}
