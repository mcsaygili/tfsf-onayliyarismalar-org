<?php

namespace App\Policies;

use App\Models\CompetitionRegistration;
use App\Models\InstitutionStaff;
use App\Models\Temsilci;
use App\Models\User;
use Illuminate\Auth\Access\Response;
use Illuminate\Database\Eloquent\Model;

class CompetitionRegistrationPolicy
{
    public function view(Model $actor, CompetitionRegistration $registration): Response
    {
        if ($actor instanceof User && $actor->id === $registration->user_id && $actor->status === 1) {
            return Response::allow();
        }

        return $this->review($actor, $registration);
    }

    public function review(Model $actor, CompetitionRegistration $registration): Response
    {
        $competition = $registration->competition;
        if (! $registration->submitted_at) {
            return Response::denyAsNotFound();
        }
        $allowed = match (true) {
            $actor instanceof InstitutionStaff => $actor->status && $registration->reviewer === 'institution' && app(\App\Services\InstitutionCompetitionAccess::class)->allows($competition, $actor),
            $actor instanceof Temsilci => $actor->status && $registration->reviewer === 'representative' && $actor->id === $competition->representative_id,
            default => false,
        };

        return $allowed ? Response::allow() : Response::denyAsNotFound();
    }
}
