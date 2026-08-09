<?php

namespace Tests\Feature\Eys;

use App\Enums\Module;
use App\Models\EysUser;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class PermissionTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): EysUser
    {
        $user = EysUser::factory()->create();

        app(PermissionRegistrar::class)->setPermissionsTeamId(Module::Eys->value);
        Permission::firstOrCreate(['name' => 'eys.permissions.manage', 'guard_name' => 'eys']);
        $user->givePermissionTo('eys.permissions.manage');

        return $user;
    }

    public function test_izin_listesi_goruntulenebilir(): void
    {
        $user = $this->admin();

        $response = $this->actingAs($user, 'eys')->get(route('eys.permissions.index'));

        $response->assertOk();
    }

    public function test_yeni_izin_olusturulabilir(): void
    {
        $user = $this->admin();

        $response = $this->actingAs($user, 'eys')->post(route('eys.permissions.store'), [
            'module' => 'Eys',
            'name' => 'eys.reports.view',
            'group' => 'Raporlar',
            'label' => 'Raporlar · Görüntüleme',
        ]);

        $response->assertRedirect(route('eys.permissions.index'));
        $this->assertDatabaseHas('permissions', ['name' => 'eys.reports.view', 'guard_name' => 'eys']);
    }

    public function test_izin_adi_regex_kurallarina_uymalidir(): void
    {
        $user = $this->admin();

        $response = $this->actingAs($user, 'eys')->post(route('eys.permissions.store'), [
            'module' => 'Eys',
            'name' => 'Eys Reports View!',
        ]);

        $response->assertSessionHasErrors('name');
    }

    public function test_kullanimdaki_izin_silinemez(): void
    {
        $user = $this->admin();

        $permission = Permission::create(['name' => 'eys.reports.view', 'guard_name' => 'eys', 'module' => 'Eys']);

        app(PermissionRegistrar::class)->setPermissionsTeamId(Module::Eys->value);
        $role = Role::create(['name' => 'editor', 'label' => 'Editör', 'guard_name' => 'eys', 'team_id' => Module::Eys->value]);
        $role->givePermissionTo($permission);

        $response = $this->actingAs($user, 'eys')->delete(route('eys.permissions.destroy', $permission));

        $response->assertRedirect(route('eys.permissions.index'));
        $this->assertModelExists($permission);
    }
}
