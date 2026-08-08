<?php

namespace Tests\Feature\Auth;

use App\Models\Temsilci;
use App\Notifications\Temsilci\ResetPasswordNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class TemsilciAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_temsilci_giris_ekrani_goruntulenebilir(): void
    {
        $this->get(route('temsilci.login'))->assertOk();
    }

    public function test_temsilci_dogru_bilgilerle_giris_yapabilir(): void
    {
        $temsilci = Temsilci::factory()->create();

        $response = $this->post(route('temsilci.login'), [
            'email' => $temsilci->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($temsilci, 'temsilci');
        $response->assertRedirect(route('temsilci.dashboard', absolute: false));
    }

    public function test_pasif_temsilci_giris_yapamaz(): void
    {
        $temsilci = Temsilci::factory()->create(['status' => false]);

        $this->post(route('temsilci.login'), [
            'email' => $temsilci->email,
            'password' => 'password',
        ]);

        $this->assertGuest('temsilci');
    }

    public function test_temsilci_cikis_yapabilir(): void
    {
        $temsilci = Temsilci::factory()->create();

        $response = $this->actingAs($temsilci, 'temsilci')->post(route('temsilci.logout'));

        $this->assertGuest('temsilci');
        $response->assertRedirect(route('temsilci.login'));
    }

    public function test_temsilci_girisi_diger_3_guard_i_authenticate_etmez(): void
    {
        $temsilci = Temsilci::factory()->create();

        $this->post(route('temsilci.login'), [
            'email' => $temsilci->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($temsilci, 'temsilci');
        $this->assertGuest('web');
        $this->assertGuest('institution');
        $this->assertGuest('juri');
    }

    public function test_temsilci_sifre_sifirlama_linki_temsilci_broker_uzerinden_gonderilir(): void
    {
        Notification::fake();

        $temsilci = Temsilci::factory()->create();

        $this->post(route('temsilci.password.email'), ['email' => $temsilci->email]);

        Notification::assertSentTo($temsilci, ResetPasswordNotification::class);
    }
}
