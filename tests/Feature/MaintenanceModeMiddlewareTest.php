<?php

namespace Tests\Feature;

use App\Models\EysUser;
use App\Models\InstitutionStaff;
use App\Models\Juri;
use App\Models\MaintenanceMode;
use App\Models\Temsilci;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * CheckMaintenanceMode middleware — 4 subdomain (institution/temsilci/juri/uye)
 * için ayrı ayrı, EYS her zaman hariç. bootstrap/app.php'deki
 * prependToPriorityList() düzeltmesi olmadan bu middleware, 'auth:*' ile
 * aynı route'ta iken Laravel'in $middlewarePriority sıralaması yüzünden
 * hiç çalışmıyordu — bu testler tam olarak o regresyonu yakalar.
 */
class MaintenanceModeMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    public function test_kurum_bakim_modu_acikken_oturum_kapatilir_ve_tek_sayfa_donulur(): void
    {
        $staff = InstitutionStaff::factory()->create();
        MaintenanceMode::query()->create(['module' => 'institution', 'enabled' => true, 'message' => 'Kurum testi.']);

        $response = $this->actingAs($staff, 'institution')->get(route('institution.dashboard'));

        $response->assertStatus(503);
        $response->assertSee('Kurum testi.');
        $this->assertGuest('institution');
    }

    public function test_temsilci_bakim_modu_acikken_oturum_kapatilir_ve_tek_sayfa_donulur(): void
    {
        $temsilci = Temsilci::factory()->create();
        MaintenanceMode::query()->create(['module' => 'temsilci', 'enabled' => true, 'message' => 'Temsilci testi.']);

        $response = $this->actingAs($temsilci, 'temsilci')->get(route('temsilci.dashboard'));

        $response->assertStatus(503);
        $response->assertSee('Temsilci testi.');
        $this->assertGuest('temsilci');
    }

    public function test_juri_bakim_modu_acikken_oturum_kapatilir_ve_tek_sayfa_donulur(): void
    {
        $juri = Juri::factory()->create();
        MaintenanceMode::query()->create(['module' => 'juri', 'enabled' => true, 'message' => 'Jüri testi.']);

        $response = $this->actingAs($juri, 'juri')->get(route('juri.dashboard'));

        $response->assertStatus(503);
        $response->assertSee('Jüri testi.');
        $this->assertGuest('juri');
    }

    public function test_uye_bakim_modu_acikken_oturum_kapatilir_ve_tek_sayfa_donulur(): void
    {
        $user = User::factory()->create();
        MaintenanceMode::query()->create(['module' => 'uye', 'enabled' => true, 'message' => 'Üye testi.']);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertStatus(503);
        $response->assertSee('Üye testi.');
        $this->assertGuest();
    }

    public function test_bakim_modu_kapaliyken_normal_erisim_devam_eder(): void
    {
        $staff = InstitutionStaff::factory()->create();
        MaintenanceMode::query()->create(['module' => 'institution', 'enabled' => false]);

        $response = $this->actingAs($staff, 'institution')->get(route('institution.dashboard'));

        $response->assertOk();
    }

    public function test_mesaj_bos_ise_varsayilan_metin_gosterilir(): void
    {
        $staff = InstitutionStaff::factory()->create();
        MaintenanceMode::query()->create(['module' => 'institution', 'enabled' => true, 'message' => null]);

        $response = $this->actingAs($staff, 'institution')->get(route('institution.dashboard'));

        $response->assertStatus(503);
        $response->assertSee(__('institution.maintenance.default_message'));
    }

    public function test_eys_bakim_moduna_tabi_degil(): void
    {
        // Aynı anda TÜM 4 subdomain bakımda olsa bile EYS her zaman erişilebilir olmalı.
        foreach (MaintenanceMode::MODULES as $module) {
            MaintenanceMode::query()->create(['module' => $module, 'enabled' => true]);
        }

        $eysUser = EysUser::factory()->create();

        $response = $this->actingAs($eysUser, 'eys')->get(route('eys.dashboard'));

        $response->assertOk();
    }
}
