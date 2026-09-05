<?php

namespace Tests\Concerns;

use App\Models\InstitutionStaff;
use App\Models\Permission;
use App\Services\SecretariatService;

trait CreatesSecretariat
{
    use CreatesRegistrationException;

    private function secretariatFixture(): array
    {
        $f = $this->exceptionFixture(0);
        Permission::firstOrCreate(['name' => 'institution.secretariats.manage', 'guard_name' => 'eys']);
        $f['manager']->givePermissionTo('institution.secretariats.manage');
        $secretariat = InstitutionStaff::factory()->create(['account_kind' => 'secretariat', 'institution_id' => null]);
        app(SecretariatService::class)->assign($f['competition'], $f['manager'], $secretariat->id, 0, 'Synthetic secretariat assignment.');

        return $f + compact('secretariat');
    }
}
