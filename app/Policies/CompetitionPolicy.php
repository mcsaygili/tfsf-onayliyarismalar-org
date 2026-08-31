<?php

namespace App\Policies;

use App\Enums\Module;
use App\Models\Competition;
use App\Models\EysUser;
use App\Models\InstitutionStaff;
use App\Models\Juri;
use App\Models\Temsilci;
use Illuminate\Auth\Access\Response;
use Spatie\Permission\PermissionRegistrar;

class CompetitionPolicy
{
    public function update(InstitutionStaff $staff, Competition $competition): Response
    {
        return $staff->institution_id === $competition->institution_id
            ? Response::allow()
            : Response::denyAsNotFound();
    }

    public function manage(EysUser $user, Competition $competition): bool
    {
        if (! $user->status) {
            return false;
        }

        $permissions = app(PermissionRegistrar::class);
        $previousTeam = $permissions->getPermissionsTeamId();
        $permissions->setPermissionsTeamId(Module::Institution->value);

        try {
            return $user->hasPermissionTo('institution.competitions.manage', 'eys');
        } finally {
            $permissions->setPermissionsTeamId($previousTeam);
        }
    }

    public function operate(Temsilci $representative, Competition $competition): Response
    {
        return $representative->status && $competition->representative_id === $representative->id
            ? Response::allow()
            : Response::denyAsNotFound();
    }

    public function evaluate(Juri $juror, Competition $competition): Response
    {
        $assigned = $juror->status && $competition->categories()
            ->whereHas('jurorAssignments', fn ($query) => $query->where('juror_id', $juror->id))
            ->exists();

        return $assigned ? Response::allow() : Response::denyAsNotFound();
    }
}
