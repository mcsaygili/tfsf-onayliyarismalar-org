<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Notifications\Uye\VerifyEmailNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class UyeRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_kayit_ekrani_goruntulenebilir(): void
    {
        $this->get(route('register'))->assertOk();
    }

    public function test_gecerli_bilgilerle_kayit_uye_olusturur(): void
    {
        Notification::fake();

        $response = $this->post(route('register'), [
            'first_name' => 'Ayşe',
            'last_name' => 'Yılmaz',
            'username' => 'ayseyilmaz',
            'email' => 'yeni@ornek-uye.test',
            'password' => 'gizli1234',
            'password_confirmation' => 'gizli1234',
        ]);

        $user = User::where('email', 'yeni@ornek-uye.test')->firstOrFail();

        $this->assertSame('Ayşe', $user->first_name);
        $this->assertSame('ayseyilmaz', $user->username);
        $this->assertNull($user->email_verified_at);
        $this->assertGuest('web');
        $response->assertRedirect(route('login'));

        Notification::assertSentTo($user, VerifyEmailNotification::class);
    }

    public function test_sifreler_eslesmezse_kayit_reddedilir(): void
    {
        $this->post(route('register'), [
            'first_name' => 'Test',
            'last_name' => 'Kullanıcı',
            'username' => 'testkullanici',
            'email' => 'test@ornek-uye.test',
            'password' => 'gizli1234',
            'password_confirmation' => 'farkli-sifre',
        ]);

        $this->assertDatabaseMissing('users', ['email' => 'test@ornek-uye.test']);
    }

    public function test_ayni_e_posta_ile_ikinci_kez_kayit_olunamaz(): void
    {
        User::factory()->create(['email' => 'var-olan@ornek-uye.test']);

        $response = $this->post(route('register'), [
            'first_name' => 'Test',
            'last_name' => 'Kullanıcı',
            'username' => 'baskakullanici',
            'email' => 'var-olan@ornek-uye.test',
            'password' => 'gizli1234',
            'password_confirmation' => 'gizli1234',
        ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_ayni_kullanici_adi_ile_ikinci_kez_kayit_olunamaz(): void
    {
        User::factory()->create(['username' => 'varolankullanici']);

        $response = $this->post(route('register'), [
            'first_name' => 'Test',
            'last_name' => 'Kullanıcı',
            'username' => 'varolankullanici',
            'email' => 'benzersiz@ornek-uye.test',
            'password' => 'gizli1234',
            'password_confirmation' => 'gizli1234',
        ]);

        $response->assertSessionHasErrors('username');
    }

    public function test_dogrulanmamis_uye_panele_erisemez(): void
    {
        $user = User::factory()->unverified()->create();

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertRedirect(route('verification.notice'));
    }

    public function test_dogrulanmis_uye_panele_erisebilir(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
    }

    /**
     * Regresyon testi: bkz. TemsilciRegistrationTest'teki eşdeğeri —
     * DOMAIN_UYE artık base domain'den ayrı, kendi subdomain'ine
     * (uye.*) sahip; doğrulama linkinin doğru yere gittiğini garanti eder.
     */
    public function test_dogrulama_linki_uye_subdomain_ine_gider(): void
    {
        $user = User::factory()->unverified()->create();

        $mail = (new VerifyEmailNotification)->toMail($user);

        $this->assertStringStartsWith(
            'http://'.config('domains.uye'),
            $mail->actionUrl
        );
    }
}
