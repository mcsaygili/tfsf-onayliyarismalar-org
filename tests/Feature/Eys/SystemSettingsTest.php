<?php

namespace Tests\Feature\Eys;

use App\Enums\Module;
use App\Models\EysUser;
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
}
