<?php

namespace App\Services;

use App\Enums\Module;
use App\Models\Competition;
use App\Models\EysUser;
use App\Models\InstitutionStaff;
use App\Models\RegistrationExceptionGrant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\PermissionRegistrar;

class SecretariatService
{
    public function authorize(EysUser $manager): void
    {
        $registrar = app(PermissionRegistrar::class);
        $previous = $registrar->getPermissionsTeamId();
        $registrar->setPermissionsTeamId(Module::Institution->value);
        try {
            $manager = $manager->fresh();
            abort_unless($manager?->status && $manager->checkPermissionTo('institution.secretariats.manage', 'eys'), 403);
        } finally {
            $registrar->setPermissionsTeamId($previous);
        }
    }

    public function create(EysUser $manager, array $data): InstitutionStaff
    {
        return DB::transaction(function () use ($manager, $data) {
            $this->authorize($manager);
            $account = new InstitutionStaff;
            $account->forceFill(['account_kind' => 'secretariat', 'institution_id' => null]);
            $account->fill(collect($data)->only(['first_name', 'last_name', 'email', 'password', 'phone', 'status'])->all())->save();
            $this->event($account, $manager, 'created', ['status' => $account->status, 'email' => $account->email]);

            return $account;
        });
    }

    public function update(EysUser $manager, InstitutionStaff $account, array $data): void
    {
        DB::transaction(function () use ($manager, $account, $data) {
            $this->authorize($manager);
            $account = InstitutionStaff::whereKey($account->id)->lockForUpdate()->firstOrFail();
            abort_unless($account->isSecretariat(), 404);
            $this->version($account, $data['context']);
            $before = $account->only(['first_name', 'last_name', 'email', 'phone', 'status']);
            $account->fill(collect($data)->only(array_keys($before))->all());
            if ($account->isDirty('email')) {
                $account->email_verified_at = null;
            }
            $account->save();
            $this->event($account, $manager, 'updated', ['before' => $before, 'after' => $account->only(array_keys($before))]);
        });
    }

    public function context(InstitutionStaff $account): string
    {
        return hash_hmac('sha256', json_encode($account->only(['id', 'account_kind', 'institution_id', 'first_name', 'last_name', 'email', 'phone', 'status', 'security_stamp']), JSON_THROW_ON_ERROR), config('app.key'));
    }

    public function profile(InstitutionStaff $account, array $data): void
    {
        DB::transaction(function () use ($account, $data) {
            $account = InstitutionStaff::whereKey($account->id)->lockForUpdate()->firstOrFail();
            abort_unless($account->isSecretariat() && $account->status, 403);
            $this->version($account, $data['context']);
            $before = $account->only(['first_name', 'last_name', 'phone']);
            $account->fill(collect($data)->only(array_keys($before))->all())->save();
            $this->event($account, $account, 'profile_updated', ['before' => $before, 'after' => $account->only(array_keys($before))]);
        });
    }

    public function assign(Competition $competition, EysUser $manager, ?string $accountId, int $version, string $reason): void
    {
        DB::transaction(function () use ($competition, $manager, $accountId, $version, $reason) {
            $competition = CompetitionMutationLock::acquire($competition->id);
            $this->authorize($manager);
            Gate::forUser($manager->fresh())->authorize('manage', $competition);
            if ($competition->secretariat_version !== $version) {
                throw ValidationException::withMessages(['secretariat' => __('secretariat.stale')]);
            }
            if (mb_strlen(trim($reason)) < 10 || mb_strlen($reason) > 2000) {
                throw ValidationException::withMessages(['reason' => __('registration.exception_reason_required')]);
            }
            if ($accountId) {
                $account = InstitutionStaff::whereKey($accountId)->lockForUpdate()->firstOrFail();
                abort_unless($account->isSecretariat() && $account->status && $account->email_verified_at, 422);
            }
            $previous = $competition->secretariat_id;
            $competition->forceFill(['secretariat_id' => $accountId, 'secretariat_version' => $version + 1])->save();
            if ($previous && $previous !== $accountId) {
                RegistrationExceptionGrant::where('competition_id', $competition->id)->where('actor_type', InstitutionStaff::class)->where('actor_id', $previous)->where('active', true)
                    ->update(['active' => false, 'version' => DB::raw('version + 1'), 'updated_by' => $manager->id, 'reason' => trim($reason), 'updated_at' => now()]);
            }
            app(CompetitionAuditService::class)->record($competition, 'secretariat_assigned', $manager, trim($reason), ['previous_secretariat_id' => $previous, 'secretariat_id' => $accountId, 'version' => $version + 1]);
        });
    }

    private function version(InstitutionStaff $account, string $context): void
    {
        if (! hash_equals($this->context($account), $context)) {
            throw ValidationException::withMessages(['context' => __('secretariat.stale')]);
        }
    }

    private function event(InstitutionStaff $account, Model $actor, string $action, array $changes): void
    {
        DB::table('secretariat_account_events')->insert(['id' => (string) Str::uuid(), 'account_id' => $account->id, 'actor_type' => $actor::class, 'actor_id' => $actor->id, 'action' => $action, 'changes' => json_encode($changes, JSON_THROW_ON_ERROR), 'created_at' => now()]);
    }
}
