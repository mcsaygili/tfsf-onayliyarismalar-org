<?php

namespace Tests\Feature\Auth;

use App\Models\Temsilci;
use App\Notifications\Temsilci\VerifyEmailNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class TemsilciRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_kayit_ekrani_goruntulenebilir(): void
    {
        $this->get(route('temsilci.register'))->assertOk();
    }

    public function test_gecerli_bilgilerle_kayit_temsilci_olusturur(): void
    {
        Notification::fake();

        $response = $this->post(route('temsilci.register'), [
            'email' => 'yeni@ornek-temsilci.test',
            'password' => 'gizli1234',
            'password_confirmation' => 'gizli1234',
        ]);

        $temsilci = Temsilci::where('email', 'yeni@ornek-temsilci.test')->firstOrFail();

        $this->assertNull($temsilci->email_verified_at);
        $this->assertNull($temsilci->first_name);
        $this->assertGuest('temsilci');
        $response->assertRedirect(route('temsilci.login'));

        Notification::assertSentTo($temsilci, VerifyEmailNotification::class);
    }

    public function test_sifreler_eslesmezse_kayit_reddedilir(): void
    {
        $this->post(route('temsilci.register'), [
            'email' => 'test@ornek-temsilci.test',
            'password' => 'gizli1234',
            'password_confirmation' => 'farkli-sifre',
        ]);

        $this->assertDatabaseMissing('representatives', ['email' => 'test@ornek-temsilci.test']);
    }

    public function test_ayni_e_posta_ile_ikinci_kez_kayit_olunamaz(): void
    {
        Temsilci::factory()->create(['email' => 'var-olan@ornek-temsilci.test']);

        $response = $this->post(route('temsilci.register'), [
            'email' => 'var-olan@ornek-temsilci.test',
            'password' => 'gizli1234',
            'password_confirmation' => 'gizli1234',
        ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_dogrulanmamis_temsilci_panele_erisemez(): void
    {
        $temsilci = Temsilci::factory()->unverified()->create();

        $response = $this->actingAs($temsilci, 'temsilci')->get(route('temsilci.dashboard'));

        $response->assertRedirect(route('temsilci.verification.notice'));
    }

    public function test_dogrulanmis_temsilci_panele_erisebilir(): void
    {
        $temsilci = Temsilci::factory()->create();

        $response = $this->actingAs($temsilci, 'temsilci')->get(route('temsilci.dashboard'));

        $response->assertOk();
    }

    /**
     * Regresyon testi: bkz. InstitutionRegistrationTest'teki eşdeğeri —
     * Laravel'in temel VerifyEmail bildirimi route adını `verification.verify`
     * olarak sabitliyor; guard'a özgü route'u açıkça kullanmak doğrulama
     * linkinin doğru subdomain'e (temsilci.*) gitmesini garanti eder.
     */
    public function test_dogrulama_linki_temsilci_subdomain_ine_gider(): void
    {
        $temsilci = Temsilci::factory()->unverified()->create();

        $mail = (new VerifyEmailNotification)->toMail($temsilci);

        $this->assertStringStartsWith(
            'http://'.config('domains.temsilci'),
            $mail->actionUrl
        );
    }
}
