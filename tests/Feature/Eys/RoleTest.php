<?php

namespace Tests\Feature\Eys;

use App\Enums\Module;
use App\Models\EysUser;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class RoleTest extends TestCase
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

    public function test_rol_listesi_goruntulenebilir(): void
    {
        $user = $this->admin();

        $response = $this->actingAs($user, 'eys')->get(route('eys.roles.index'));

        $response->assertOk();
    }

    public function test_yeni_rol_olusturulabilir_ve_sadece_kendi_modulunun_izinlerini_tasir(): void
    {
        $user = $this->admin();

        $eysPerm = Permission::create(['name' => 'eys.countries.view', 'guard_name' => 'eys', 'module' => 'Eys', 'group' => 'Ülke Yönetimi']);
        $otherPerm = Permission::create(['name' => 'institution.staff.view', 'guard_name' => 'eys', 'module' => 'Institution', 'group' => 'Kurum Yetkilileri']);

        $response = $this->actingAs($user, 'eys')->post(route('eys.roles.store'), [
            'module' => 'Eys',
            'name' => 'editor',
            'label' => 'Editör',
            // Institution iznini de göndermeyi deniyoruz — sızma engellenmeli.
            'permissions' => [$eysPerm->name, $otherPerm->name],
        ]);

        $response->assertRedirect(route('eys.roles.index'));

        $role = Role::where('name', 'editor')->where('team_id', Module::Eys->value)->firstOrFail();

        $this->assertSame([$eysPerm->name], $role->permissions->pluck('name')->all());
    }

    public function test_ayni_rol_adi_farkli_modullerde_bagimsiz_olabilir(): void
    {
        $user = $this->admin();

        $this->actingAs($user, 'eys')->post(route('eys.roles.store'), [
            'module' => 'Eys',
            'name' => 'editor',
            'label' => 'Editör',
            'permissions' => [],
        ]);

        $response = $this->actingAs($user, 'eys')->post(route('eys.roles.store'), [
            'module' => 'Institution',
            'name' => 'editor',
            'label' => 'Editör',
            'permissions' => [],
        ]);

        $response->assertRedirect(route('eys.roles.index'));
        $this->assertSame(2, Role::where('name', 'editor')->count());
    }

    public function test_kullanimdaki_rol_silinemez(): void
    {
        $user = $this->admin();

        app(PermissionRegistrar::class)->setPermissionsTeamId(Module::Eys->value);
        $role = Role::create(['name' => 'editor', 'label' => 'Editör', 'guard_name' => 'eys', 'team_id' => Module::Eys->value]);

        $target = EysUser::factory()->create();
        $target->assignRole($role);

        $response = $this->actingAs($user, 'eys')->delete(route('eys.roles.destroy', $role));

        $response->assertRedirect(route('eys.roles.index'));
        $this->assertModelExists($role);
    }

    public function test_kullanilmayan_rol_silinebilir(): void
    {
        $user = $this->admin();

        app(PermissionRegistrar::class)->setPermissionsTeamId(Module::Eys->value);
        $role = Role::create(['name' => 'editor', 'label' => 'Editör', 'guard_name' => 'eys', 'team_id' => Module::Eys->value]);

        $response = $this->actingAs($user, 'eys')->delete(route('eys.roles.destroy', $role));

        $response->assertRedirect(route('eys.roles.index'));
        $this->assertModelMissing($role);
    }

    public function test_izinsiz_kullanici_roller_sayfasina_erisemez(): void
    {
        $user = EysUser::factory()->create();

        $response = $this->actingAs($user, 'eys')->get(route('eys.roles.index'));

        $response->assertForbidden();
    }
}
