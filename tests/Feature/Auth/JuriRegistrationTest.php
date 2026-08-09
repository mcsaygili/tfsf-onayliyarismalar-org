<?php

namespace Tests\Feature\Auth;

use App\Models\Juri;
use App\Notifications\Juri\VerifyEmailNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class JuriRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_kayit_ekrani_goruntulenebilir(): void
    {
        $this->get(route('juri.register'))->assertOk();
    }

    public function test_gecerli_bilgilerle_kayit_juri_olusturur(): void
    {
        Notification::fake();

        $response = $this->post(route('juri.register'), [
            'email' => 'yeni@ornek-juri.test',
            'password' => 'gizli1234',
            'password_confirmation' => 'gizli1234',
        ]);

        $juri = Juri::where('email', 'yeni@ornek-juri.test')->firstOrFail();

        $this->assertNull($juri->email_verified_at);
        $this->assertNull($juri->first_name);
        $this->assertGuest('juri');
        $response->assertRedirect(route('juri.login'));

        Notification::assertSentTo($juri, VerifyEmailNotification::class);
    }

    public function test_sifreler_eslesmezse_kayit_reddedilir(): void
    {
        $this->post(route('juri.register'), [
            'email' => 'test@ornek-juri.test',
            'password' => 'gizli1234',
            'password_confirmation' => 'farkli-sifre',
        ]);

        $this->assertDatabaseMissing('jurors', ['email' => 'test@ornek-juri.test']);
    }

    public function test_ayni_e_posta_ile_ikinci_kez_kayit_olunamaz(): void
    {
        Juri::factory()->create(['email' => 'var-olan@ornek-juri.test']);

        $response = $this->post(route('juri.register'), [
            'email' => 'var-olan@ornek-juri.test',
            'password' => 'gizli1234',
            'password_confirmation' => 'gizli1234',
        ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_dogrulanmamis_juri_panele_erisemez(): void
    {
        $juri = Juri::factory()->unverified()->create();

        $response = $this->actingAs($juri, 'juri')->get(route('juri.dashboard'));

        $response->assertRedirect(route('juri.verification.notice'));
    }

    public function test_dogrulanmis_juri_panele_erisebilir(): void
    {
        $juri = Juri::factory()->create();

        $response = $this->actingAs($juri, 'juri')->get(route('juri.dashboard'));

        $response->assertOk();
    }

    /**
     * Regresyon testi: bkz. InstitutionRegistrationTest'teki eşdeğeri.
     */
    public function test_dogrulama_linki_juri_subdomain_ine_gider(): void
    {
        $juri = Juri::factory()->unverified()->create();

        $mail = (new VerifyEmailNotification)->toMail($juri);

        $this->assertStringStartsWith(
            'http://'.config('domains.juri'),
            $mail->actionUrl
        );
    }
}
