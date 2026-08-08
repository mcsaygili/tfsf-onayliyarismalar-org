<?php

namespace Tests\Feature\Auth;

use App\Models\Institution;
use App\Models\InstitutionStaff;
use App\Notifications\Institution\VerifyEmailNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class InstitutionRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_kayit_ekrani_goruntulenebilir(): void
    {
        $this->get(route('institution.register'))->assertOk();
    }

    public function test_gecerli_bilgilerle_kayit_kurum_ve_personel_olusturur(): void
    {
        Notification::fake();

        $response = $this->post(route('institution.register'), [
            'email' => 'yeni@ornek-kurum.test',
            'password' => 'gizli1234',
            'password_confirmation' => 'gizli1234',
        ]);

        $staff = InstitutionStaff::where('email', 'yeni@ornek-kurum.test')->firstOrFail();

        $this->assertNotNull($staff->institution_id);
        $this->assertNull($staff->email_verified_at);
        $this->assertGuest('institution');
        $response->assertRedirect(route('institution.login'));

        Notification::assertSentTo($staff, VerifyEmailNotification::class);
    }

    public function test_kayit_olan_kurum_otomatik_onaylanir(): void
    {
        $this->post(route('institution.register'), [
            'email' => 'onayli@ornek-kurum.test',
            'password' => 'gizli1234',
            'password_confirmation' => 'gizli1234',
        ]);

        $staff = InstitutionStaff::where('email', 'onayli@ornek-kurum.test')->firstOrFail();

        $this->assertTrue($staff->institution->isApproved());
        $this->assertNotNull($staff->institution->approved_at);
    }

    public function test_kurum_adi_kayit_aninda_bos_birakilir(): void
    {
        $this->post(route('institution.register'), [
            'email' => 'isimsiz@ornek-kurum.test',
            'password' => 'gizli1234',
            'password_confirmation' => 'gizli1234',
        ]);

        $staff = InstitutionStaff::where('email', 'isimsiz@ornek-kurum.test')->firstOrFail();

        $this->assertNull($staff->institution->name);
        $this->assertNull($staff->first_name);
        $this->assertNull($staff->last_name);
    }

    public function test_sifreler_eslesmezse_kayit_reddedilir(): void
    {
        $this->post(route('institution.register'), [
            'email' => 'test@ornek-kurum.test',
            'password' => 'gizli1234',
            'password_confirmation' => 'farkli-sifre',
        ]);

        $this->assertDatabaseMissing('institution_staff', ['email' => 'test@ornek-kurum.test']);
    }

    public function test_ayni_e_posta_ile_ikinci_kez_kayit_olunamaz(): void
    {
        $institution = Institution::factory()->create();
        InstitutionStaff::factory()->for($institution)->create(['email' => 'var-olan@ornek-kurum.test']);

        $response = $this->post(route('institution.register'), [
            'email' => 'var-olan@ornek-kurum.test',
            'password' => 'gizli1234',
            'password_confirmation' => 'gizli1234',
        ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_dogrulanmamis_kurum_personeli_panele_erisemez(): void
    {
        $institution = Institution::factory()->create();
        $staff = InstitutionStaff::factory()->for($institution)->unverified()->create();

        $response = $this->actingAs($staff, 'institution')->get(route('institution.dashboard'));

        $response->assertRedirect(route('institution.verification.notice'));
    }

    public function test_dogrulanmis_kurum_personeli_panele_erisebilir(): void
    {
        $institution = Institution::factory()->create();
        $staff = InstitutionStaff::factory()->for($institution)->create();

        $response = $this->actingAs($staff, 'institution')->get(route('institution.dashboard'));

        $response->assertOk();
    }

    /**
     * Regresyon testi: Laravel'in temel VerifyEmail bildirimi route adını
     * `verification.verify` olarak sabitliyor — bu, önek'siz olduğu için
     * yanlışlıkla Uye modülünün (kök domain) route'uyla eşleşip doğrulama
     * linkinin kurum.* yerine kök domain'e gitmesine yol açabiliyordu
     * (bkz. VerifyEmailNotification::verificationUrl() override'ı).
     */
    public function test_dogrulama_linki_kurum_subdomain_ine_gider(): void
    {
        $institution = Institution::factory()->create();
        $staff = InstitutionStaff::factory()->for($institution)->unverified()->create();

        $mail = (new VerifyEmailNotification)->toMail($staff);

        $this->assertStringStartsWith(
            'http://'.config('domains.institution'),
            $mail->actionUrl
        );
    }
}
