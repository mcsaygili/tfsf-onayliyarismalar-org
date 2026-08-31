<?php

namespace App\Policies;

use App\Models\CompetitionCategory;
use App\Models\Juri;
use Illuminate\Auth\Access\Response;

class CompetitionCategoryPolicy
{
    public function evaluate(Juri $juror, CompetitionCategory $category): Response
    {
        return $juror->status && $category->jurorAssignments()->where('juror_id', $juror->id)->exists()
            ? Response::allow()
            : Response::denyAsNotFound();
    }
}
