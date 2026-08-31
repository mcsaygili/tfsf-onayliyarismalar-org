<?php

namespace Tests\Feature\Auth;

use App\Models\Institution;
use App\Models\InstitutionStaff;
use App\Models\Juri;
use App\Models\Temsilci;
use App\Models\User;
use App\Notifications\Institution\ResetPasswordNotification as InstitutionResetPasswordNotification;
use App\Notifications\Juri\ResetPasswordNotification as JuriResetPasswordNotification;
use App\Notifications\Temsilci\ResetPasswordNotification as TemsilciResetPasswordNotification;
use App\Notifications\Uye\ResetPasswordNotification as UyeResetPasswordNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class UyeAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_uye_giris_ekrani_goruntulenebilir(): void
    {
        $this->get(route('login'))->assertOk();
    }

    public function test_giris_yapmamis_ziyaretci_anasayfada_login_e_yonlenir(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
    }

    public function test_uye_dogru_bilgilerle_giris_yapabilir(): void
    {
        $user = User::factory()->create();

        $response = $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($user, 'web');
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_uye_yanlis_sifreyle_giris_yapamaz(): void
    {
        $user = User::factory()->create();

        $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'yanlis-sifre',
        ]);

        $this->assertGuest('web');
    }

    public function test_hesap_kisitlamasi_bulunan_uye_giris_yapamaz(): void
    {
        $user = User::factory()->create();
        $user->restrictions()->create([
            'type' => 'account',
            'reason' => 'Güvenlik incelemesi devam ediyor.',
            'starts_at' => now()->subMinute(),
        ]);

        $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest('web');
    }

    public function test_uye_cikis_yapabilir(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('logout'));

        $this->assertGuest('web');
        $response->assertRedirect(route('login'));
    }

    public function test_uye_giris_yapinca_diger_3_guard_halen_authenticate_degil(): void
    {
        $user = User::factory()->create();

        $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($user, 'web');
        $this->assertGuest('institution');
        $this->assertGuest('temsilci');
        $this->assertGuest('juri');
    }

    public function test_uye_sifre_sifirlama_linki_users_broker_uzerinden_gonderilir(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post(route('password.email'), ['email' => $user->email]);

        Notification::assertSentTo($user, UyeResetPasswordNotification::class);
    }

    public function test_institution_temsilci_juri_sifre_sifirlama_istegi_uye_tablosuna_bildirim_gondermez(): void
    {
        Notification::fake();

        $institution = Institution::create(['name' => 'Test Institution', 'status' => true]);
        InstitutionStaff::factory()->for($institution)->create(['email' => 'ayni@ornek.test']);
        $temsilci = Temsilci::factory()->create(['email' => 'ayni@ornek.test']);
        $juri = Juri::factory()->create(['email' => 'ayni@ornek.test']);
        $user = User::factory()->create(['email' => 'ayni@ornek.test']);

        $this->post(route('institution.password.email'), ['email' => 'ayni@ornek.test']);

        Notification::assertSentTo($institution->staff->first(), InstitutionResetPasswordNotification::class);
        Notification::assertNotSentTo($user, UyeResetPasswordNotification::class);
        Notification::assertNotSentTo($temsilci, TemsilciResetPasswordNotification::class);
        Notification::assertNotSentTo($juri, JuriResetPasswordNotification::class);
    }
}
