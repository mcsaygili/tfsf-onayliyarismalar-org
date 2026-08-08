<?php

namespace Tests\Feature\Auth;

use App\Models\Juri;
use App\Notifications\Juri\ResetPasswordNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class JuriAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_juri_giris_ekrani_goruntulenebilir(): void
    {
        $this->get(route('juri.login'))->assertOk();
    }

    public function test_juri_dogru_bilgilerle_giris_yapabilir(): void
    {
        $juri = Juri::factory()->create();

        $response = $this->post(route('juri.login'), [
            'email' => $juri->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($juri, 'juri');
        $response->assertRedirect(route('juri.dashboard', absolute: false));
    }

    public function test_pasif_juri_giris_yapamaz(): void
    {
        $juri = Juri::factory()->create(['status' => false]);

        $this->post(route('juri.login'), [
            'email' => $juri->email,
            'password' => 'password',
        ]);

        $this->assertGuest('juri');
    }

    public function test_juri_cikis_yapabilir(): void
    {
        $juri = Juri::factory()->create();

        $response = $this->actingAs($juri, 'juri')->post(route('juri.logout'));

        $this->assertGuest('juri');
        $response->assertRedirect(route('juri.login'));
    }

    public function test_juri_girisi_diger_3_guard_i_authenticate_etmez(): void
    {
        $juri = Juri::factory()->create();

        $this->post(route('juri.login'), [
            'email' => $juri->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($juri, 'juri');
        $this->assertGuest('web');
        $this->assertGuest('institution');
        $this->assertGuest('temsilci');
    }

    public function test_juri_sifre_sifirlama_linki_juri_broker_uzerinden_gonderilir(): void
    {
        Notification::fake();

        $juri = Juri::factory()->create();

        $this->post(route('juri.password.email'), ['email' => $juri->email]);

        Notification::assertSentTo($juri, ResetPasswordNotification::class);
    }
}
