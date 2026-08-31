<?php

namespace Tests\Feature\Eys;

use App\Enums\Module;
use App\Models\EysUser;
use App\Models\MaintenanceMode;
use App\Models\Permission;
use App\Models\PortfolioSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class SystemSettingsTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): EysUser
    {
        $user = EysUser::factory()->create();

        app(PermissionRegistrar::class)->setPermissionsTeamId(Module::Eys->value);
        Permission::firstOrCreate(['name' => 'eys.system_settings.manage', 'guard_name' => 'eys']);
        $user->givePermissionTo('eys.system_settings.manage');

        return $user;
    }

    public function test_portfolyo_ayarlari_sayfasi_goruntulenebilir(): void
    {
        $user = $this->admin();

        $response = $this->actingAs($user, 'eys')->get(route('eys.system-settings.portfolio'));

        $response->assertOk();
    }

    public function test_portfolyo_ayarlari_guncellenebilir(): void
    {
        $user = $this->admin();

        $response = $this->actingAs($user, 'eys')->patch(route('eys.system-settings.portfolio.update'), [
            'max_photos_per_user' => '50',
        ]);

        $response->assertRedirect(route('eys.system-settings.portfolio'));

        $settings = PortfolioSetting::current();
        $this->assertSame(50, $settings->max_photos_per_user);
        $this->assertSame($user->id, $settings->updated_by);
    }

    public function test_gecersiz_deger_reddedilir(): void
    {
        $user = $this->admin();

        $response = $this->actingAs($user, 'eys')->patch(route('eys.system-settings.portfolio.update'), [
            'max_photos_per_user' => '0',
        ]);

        $response->assertSessionHasErrors('max_photos_per_user');
    }

    public function test_izinsiz_kullanici_portfolyo_ayarlari_sayfasina_erisemez(): void
    {
        $user = EysUser::factory()->create();

        $response = $this->actingAs($user, 'eys')->get(route('eys.system-settings.portfolio'));

        $response->assertForbidden();
    }

    public function test_bakim_modu_sayfasi_goruntulenebilir(): void
    {
        $user = $this->admin();

        $response = $this->actingAs($user, 'eys')->get(route('eys.system-settings.maintenance'));

        $response->assertOk();
    }

    public function test_sistem_sagligi_sayfasi_yetkili_kullanici_icin_goruntulenebilir(): void
    {
        $user = $this->admin();

        $this->actingAs($user, 'eys')->get(route('eys.system-settings.health'))
            ->assertOk()
            ->assertSee('Veritabanı bağlantısı çalışıyor.')
            ->assertSee('Bildirim teslimatları');
    }

    public function test_bakim_modu_dort_subdomain_icin_ayri_ayri_guncellenebilir(): void
    {
        $user = $this->admin();

        $response = $this->actingAs($user, 'eys')->patch(route('eys.system-settings.maintenance.update'), [
            'modules' => [
                'institution' => ['enabled' => '1', 'message' => 'Kurum bakımda.'],
                'temsilci' => ['enabled' => '0', 'message' => ''],
                'juri' => ['enabled' => '0', 'message' => ''],
                'uye' => ['enabled' => '0', 'message' => ''],
            ],
        ]);

        $response->assertRedirect(route('eys.system-settings.maintenance'));

        $this->assertTrue(MaintenanceMode::isEnabledFor('institution'));
        $this->assertFalse(MaintenanceMode::isEnabledFor('temsilci'));
        $this->assertFalse(MaintenanceMode::isEnabledFor('juri'));
        $this->assertFalse(MaintenanceMode::isEnabledFor('uye'));

        $institution = MaintenanceMode::query()->where('module', 'institution')->first();
        $this->assertSame('Kurum bakımda.', $institution->message);
        $this->assertSame($user->id, $institution->updated_by);
    }

    public function test_izinsiz_kullanici_bakim_modu_sayfasina_erisemez(): void
    {
        $user = EysUser::factory()->create();

        $response = $this->actingAs($user, 'eys')->get(route('eys.system-settings.maintenance'));

        $response->assertForbidden();
    }
}
