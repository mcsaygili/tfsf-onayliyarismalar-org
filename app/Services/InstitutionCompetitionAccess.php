<?php

namespace App\Services;

use App\Models\Competition;
use App\Models\InstitutionStaff;
use Illuminate\Database\Eloquent\Builder;

class InstitutionCompetitionAccess
{
    public function scope(Builder $query, InstitutionStaff $actor): Builder
    {
        if (! $actor->status) {
            return $query->whereRaw('1 = 0');
        }

        return $actor->isSecretariat()
            ? $query->where('secretariat_id', $actor->id)->whereHas('institution', fn ($q) => $q->where('status', true))
            : $query->where('institution_id', $actor->institution_id)->whereHas('institution', fn ($q) => $q->where('status', true));
    }

    public function allows(Competition $competition, InstitutionStaff $actor): bool
    {
        return $this->scope(Competition::query()->whereKey($competition->id), $actor)->exists();
    }
}
