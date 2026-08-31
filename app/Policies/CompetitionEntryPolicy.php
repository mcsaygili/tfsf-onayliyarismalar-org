<?php

namespace App\Policies;

use App\Models\CompetitionEntry;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class CompetitionEntryPolicy
{
    public function manage(User $user, CompetitionEntry $entry): Response
    {
        return $entry->user_id === $user->id
            ? Response::allow()
            : Response::denyAsNotFound();
    }
}
