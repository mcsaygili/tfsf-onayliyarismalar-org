<?php

namespace App\Services;

use App\Models\Competition;
use App\Models\EysUser;
use App\Models\InstitutionStaff;
use App\Models\RegistrationExceptionGrant;
use App\Models\Temsilci;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class RegistrationExceptionService
{
    public function inScope(Competition $competition, Model $actor): bool
    {
        return $competition->registration_required && match (true) {
            $actor instanceof InstitutionStaff => $actor->status && $competition->registration_reviewer === 'institution' && app(InstitutionCompetitionAccess::class)->allows($competition, $actor),
            $actor instanceof Temsilci => $actor->status && $competition->registration_reviewer === 'representative' && $actor->id === $competition->representative_id,
            default => false,
        };
    }

    public function authorize(Competition $competition, Model $actor, ?int $version = null): RegistrationExceptionGrant
    {
        $actor = $actor->fresh();
        abort_unless($actor && $this->inScope($competition, $actor), 404);
        $grant = RegistrationExceptionGrant::where('competition_id', $competition->id)->where('actor_type', $actor::class)->where('actor_id', $actor->id)->where('active', true)->first();
        abort_unless($grant, 404);
        if ($version !== null && $grant->version !== $version) {
            throw ValidationException::withMessages(['registration' => __('registration.exception_stale')]);
        }

        return $grant;
    }

    public function setGrant(Competition $competition, EysUser $manager, Model $actor, int $version, bool $active, string $reason): RegistrationExceptionGrant
    {
        return DB::transaction(function () use ($competition, $manager, $actor, $version, $active, $reason) {
            $competition = CompetitionMutationLock::acquire($competition->id);
            Gate::forUser($manager->fresh())->authorize('manageRegistrationExceptions', $competition);
            $actor = $actor->fresh();
            // Revocation remains possible after reassignment or account deactivation.
            abort_unless($actor && ($actor instanceof InstitutionStaff || $actor instanceof Temsilci), 404);
            if ($active) {
                abort_unless($this->inScope($competition, $actor), 404);
            }
            $this->requireReason($reason);
            $grant = RegistrationExceptionGrant::where('competition_id', $competition->id)->where('actor_type', $actor::class)->where('actor_id', $actor->id)->lockForUpdate()->first();
            if (($grant?->version ?? 0) !== $version) {
                throw ValidationException::withMessages(['registration' => __('registration.exception_stale')]);
            }
            abort_unless($active || $grant, 404);
            $previous = $grant?->active ?? false;
            $grant ??= new RegistrationExceptionGrant(['competition_id' => $competition->id, 'actor_type' => $actor::class, 'actor_id' => $actor->id]);
            $grant->fill(['active' => $active, 'version' => $version + 1, 'reason' => trim($reason), 'updated_by' => $manager->id])->save();
            app(CompetitionAuditService::class)->record($competition, 'registration_exception_permission', $manager, trim($reason), [
                'grant_id' => $grant->id, 'grant_version' => $grant->version, 'actor_type' => $actor::class, 'actor_id' => $actor->id,
                'previous_active' => $previous, 'active' => $active,
            ]);

            return $grant;
        });
    }

    public function requireReason(string $reason): void
    {
        if (mb_strlen(trim($reason)) < 10 || mb_strlen($reason) > 2000) {
            throw ValidationException::withMessages(['reason' => __('registration.exception_reason_required')]);
        }
    }
}
