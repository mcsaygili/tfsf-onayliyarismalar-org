<?php

namespace Tests\Feature\Auth;

use App\Models\EysUser;
use App\Notifications\Eys\ResetPasswordNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class EysAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_eys_giris_ekrani_goruntulenebilir(): void
    {
        $this->get(route('eys.login'))->assertOk();
    }

    public function test_eys_kullanicisi_dogru_bilgilerle_giris_yapabilir(): void
    {
        $user = EysUser::factory()->create();

        $response = $this->post(route('eys.login'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($user, 'eys');
        $response->assertRedirect(route('eys.dashboard', absolute: false));
    }

    public function test_pasif_eys_kullanicisi_giris_yapamaz(): void
    {
        $user = EysUser::factory()->create(['status' => false]);

        $this->post(route('eys.login'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertGuest('eys');
    }

    public function test_eys_kullanicisi_cikis_yapabilir(): void
    {
        $user = EysUser::factory()->create();

        $response = $this->actingAs($user, 'eys')->post(route('eys.logout'));

        $this->assertGuest('eys');
        $response->assertRedirect(route('eys.login'));
    }

    public function test_eys_girisi_diger_4_guard_i_authenticate_etmez(): void
    {
        $user = EysUser::factory()->create();

        $this->post(route('eys.login'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($user, 'eys');
        $this->assertGuest('web');
        $this->assertGuest('institution');
        $this->assertGuest('temsilci');
        $this->assertGuest('juri');
    }

    public function test_eys_sifre_sifirlama_linki_eys_broker_uzerinden_gonderilir(): void
    {
        Notification::fake();

        $user = EysUser::factory()->create();

        $this->post(route('eys.password.email'), ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPasswordNotification::class);
    }

    /**
     * Regresyon testi: bkz. InstitutionAuthenticationTest'teki eşdeğeri —
     * Laravel'in `guest` middleware'i guard'dan bağımsız olarak bare
     * `dashboard` route'unu arıyor.
     */
    public function test_giris_yapmis_eys_kullanicisi_login_sayfasina_giderse_kendi_paneline_yonlenir(): void
    {
        $user = EysUser::factory()->create();

        $response = $this->actingAs($user, 'eys')->get(route('eys.login'));

        $response->assertRedirect(route('eys.dashboard'));
    }

    public function test_eys_register_route_u_yok(): void
    {
        $this->assertFalse(Route::has('eys.register'));
    }
}
