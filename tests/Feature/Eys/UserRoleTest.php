<?php

namespace Tests\Feature\Eys;

use App\Enums\Module;
use App\Models\EysUser;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class UserRoleTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): EysUser
    {
        $user = EysUser::factory()->create();

        app(PermissionRegistrar::class)->setPermissionsTeamId(Module::Eys->value);
        Permission::firstOrCreate(['name' => 'eys.roles.manage', 'guard_name' => 'eys']);
        $user->givePermissionTo('eys.roles.manage');

        return $user;
    }

    public function test_kullaniciya_farkli_modullerde_farkli_roller_atanabilir(): void
    {
        $admin = $this->admin();
        $target = EysUser::factory()->create();

        $registrar = app(PermissionRegistrar::class);
        $registrar->setPermissionsTeamId(Module::Eys->value);
        $eysRole = Role::create(['name' => 'viewer', 'label' => 'Görüntüleyici', 'guard_name' => 'eys', 'team_id' => Module::Eys->value]);

        $registrar->setPermissionsTeamId(Module::Institution->value);
        $institutionRole = Role::create(['name' => 'admin', 'label' => 'Yönetici', 'guard_name' => 'eys', 'team_id' => Module::Institution->value]);

        $response = $this->actingAs($admin, 'eys')->patch(route('eys.users.roles.update', $target), [
            'roles' => [
                Module::Eys->value => ['viewer'],
                Module::Institution->value => ['admin'],
            ],
        ]);

        $response->assertRedirect(route('eys.users.index'));

        $registrar->setPermissionsTeamId(Module::Eys->value);
        $this->assertTrue($target->fresh()->hasRole($eysRole->fresh()));

        $registrar->setPermissionsTeamId(Module::Institution->value);
        $this->assertTrue($target->fresh()->hasRole($institutionRole->fresh()));
    }

    public function test_var_olmayan_bir_rol_gonderilirse_yok_sayilir(): void
    {
        $admin = $this->admin();
        $target = EysUser::factory()->create();

        $response = $this->actingAs($admin, 'eys')->patch(route('eys.users.roles.update', $target), [
            'roles' => [
                Module::Eys->value => ['hayali-rol'],
            ],
        ]);

        $response->assertRedirect(route('eys.users.index'));

        app(PermissionRegistrar::class)->setPermissionsTeamId(Module::Eys->value);
        $this->assertCount(0, $target->fresh()->roles);
    }

    public function test_team_middleware_dogru_izni_kontrol_eder(): void
    {
        $registrar = app(PermissionRegistrar::class);
        $registrar->setPermissionsTeamId(Module::Eys->value);

        $user = EysUser::factory()->create();
        Permission::firstOrCreate(['name' => 'eys.roles.manage', 'guard_name' => 'eys']);
        $user->givePermissionTo('eys.roles.manage');

        $response = $this->actingAs($user, 'eys')->get(route('eys.roles.index'));

        $response->assertOk();
    }
}
