<?php

namespace App\Policies;

use App\Models\CompetitionSubmissionApproval;
use App\Models\InstitutionStaff;
use App\Models\Temsilci;
use App\Services\InstitutionCompetitionAccess;
use Illuminate\Auth\Access\Response;

class CompetitionSubmissionApprovalPolicy
{
    public function decide(InstitutionStaff|Temsilci $actor, CompetitionSubmissionApproval $approval): Response
    {
        if (! $actor->status) {
            return Response::denyAsNotFound();
        }
        $approval->loadMissing('submission.entry.competition');
        $competition = $approval->submission->entry->competition;
        $allowed = match (true) {
            $actor instanceof InstitutionStaff => $approval->approval_type === 'institution'
                && app(InstitutionCompetitionAccess::class)->allows($competition, $actor),
            $actor instanceof Temsilci => $approval->approval_type === 'representative'
                && $competition->representative_id === $actor->id,
        };

        return $allowed ? Response::allow() : Response::denyAsNotFound();
    }
}
