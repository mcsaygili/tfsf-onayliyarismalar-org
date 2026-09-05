<?php

namespace Tests\Concerns;

use App\Enums\Module;
use App\Models\EysUser;
use App\Models\Permission;
use App\Services\RegistrationExceptionService;
use Spatie\Permission\PermissionRegistrar;

trait CreatesRegistrationException
{
    use CreatesCompetitionRegistration;

    private function exceptionManager(bool $special = true): EysUser
    {
        app(PermissionRegistrar::class)->setPermissionsTeamId(Module::Institution->value);
        $manager = EysUser::factory()->create();
        $names = ['institution.competitions.manage'];
        if ($special) {
            $names[] = 'institution.registration_exceptions.manage';
        }
        foreach ($names as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'eys']);
            $manager->givePermissionTo($name);
        }

        return $manager;
    }

    private function exceptionFixture(int $minimum = 1): array
    {
        $f = $this->registrationFixture($minimum);
        $manager = $this->exceptionManager();
        $grant = app(RegistrationExceptionService::class)->setGrant($f['competition'], $manager, $f['staff'], 0, true, 'Synthetic authorized reviewer.');

        return $f + compact('manager', 'grant');
    }

    private function exceptionPayload(array $f, array $override = []): array
    {
        return array_replace(['user_id' => $f['member']->id, 'version' => $f['registration']->fresh()->version, 'grant_version' => $f['grant']->version,
            'waive_documents' => 1, 'reason' => 'Synthetic attendance evidence verified.'], $override);
    }
}
