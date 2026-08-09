<?php

namespace Tests\Feature\Eys;

use App\Enums\Module;
use App\Models\EysUser;
use App\Models\Institution;
use App\Models\Juri;
use App\Models\Permission;
use App\Models\Temsilci;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ModuleDashboardTest extends TestCase
{
    use RefreshDatabase;

    private function userWithPermission(Module $module, string $permission): EysUser
    {
        $user = EysUser::factory()->create();

        app(PermissionRegistrar::class)->setPermissionsTeamId($module->value);
        Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'eys']);
        $user->givePermissionTo($permission);

        return $user;
    }

    public function test_kurum_gosterge_paneli_toplam_sayiyi_gosterir(): void
    {
        $user = $this->userWithPermission(Module::Institution, 'institution.dashboard.view');
        Institution::factory()->count(3)->create();

        $response = $this->actingAs($user, 'eys')->get(route('eys.institution.dashboard'));

        $response->assertOk();
        $response->assertSee('3');
    }

    public function test_izinsiz_kullanici_kurum_gosterge_paneline_erisemez(): void
    {
        $user = EysUser::factory()->create();

        $response = $this->actingAs($user, 'eys')->get(route('eys.institution.dashboard'));

        $response->assertForbidden();
    }

    public function test_temsilci_gosterge_paneli_toplam_sayiyi_gosterir(): void
    {
        $user = $this->userWithPermission(Module::Temsilci, 'representative.dashboard.view');
        Temsilci::factory()->count(2)->create();

        $response = $this->actingAs($user, 'eys')->get(route('eys.temsilci.dashboard'));

        $response->assertOk();
        $response->assertSee('2');
    }

    public function test_izinsiz_kullanici_temsilci_gosterge_paneline_erisemez(): void
    {
        $user = EysUser::factory()->create();

        $response = $this->actingAs($user, 'eys')->get(route('eys.temsilci.dashboard'));

        $response->assertForbidden();
    }

    public function test_juri_gosterge_paneli_toplam_sayiyi_gosterir(): void
    {
        $user = $this->userWithPermission(Module::Juri, 'jury.dashboard.view');
        Juri::factory()->count(4)->create();

        $response = $this->actingAs($user, 'eys')->get(route('eys.juri.dashboard'));

        $response->assertOk();
        $response->assertSee('4');
    }

    public function test_izinsiz_kullanici_juri_gosterge_paneline_erisemez(): void
    {
        $user = EysUser::factory()->create();

        $response = $this->actingAs($user, 'eys')->get(route('eys.juri.dashboard'));

        $response->assertForbidden();
    }

    public function test_uye_gosterge_paneli_toplam_sayiyi_gosterir(): void
    {
        $user = $this->userWithPermission(Module::Uye, 'member.dashboard.view');
        User::factory()->count(5)->create();

        $response = $this->actingAs($user, 'eys')->get(route('eys.uye.dashboard'));

        $response->assertOk();
        $response->assertSee('5');
    }

    public function test_izinsiz_kullanici_uye_gosterge_paneline_erisemez(): void
    {
        $user = EysUser::factory()->create();

        $response = $this->actingAs($user, 'eys')->get(route('eys.uye.dashboard'));

        $response->assertForbidden();
    }
}
