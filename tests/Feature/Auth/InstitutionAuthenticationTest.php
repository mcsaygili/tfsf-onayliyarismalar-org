<?php

namespace Tests\Feature\Auth;

use App\Models\Institution;
use App\Models\InstitutionStaff;
use App\Notifications\Institution\ResetPasswordNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class InstitutionAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    private function staff(array $attributes = []): InstitutionStaff
    {
        $institution = Institution::factory()->create();

        return InstitutionStaff::factory()->for($institution)->create($attributes);
    }

    public function test_institution_giris_ekrani_goruntulenebilir(): void
    {
        $this->get(route('institution.login'))->assertOk();
    }

    public function test_kurum_personeli_dogru_bilgilerle_giris_yapabilir(): void
    {
        $staff = $this->staff();

        $response = $this->post(route('institution.login'), [
            'email' => $staff->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($staff, 'institution');
        $response->assertRedirect(route('institution.dashboard', absolute: false));
    }

    public function test_pasif_kurum_personeli_giris_yapamaz(): void
    {
        $staff = $this->staff(['status' => false]);

        $this->post(route('institution.login'), [
            'email' => $staff->email,
            'password' => 'password',
        ]);

        $this->assertGuest('institution');
    }

    public function test_kurum_personeli_cikis_yapabilir(): void
    {
        $staff = $this->staff();

        $response = $this->actingAs($staff, 'institution')->post(route('institution.logout'));

        $this->assertGuest('institution');
        $response->assertRedirect(route('institution.login'));
    }

    public function test_institution_girisi_diger_3_guard_i_authenticate_etmez(): void
    {
        $staff = $this->staff();

        $this->post(route('institution.login'), [
            'email' => $staff->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($staff, 'institution');
        $this->assertGuest('web');
        $this->assertGuest('temsilci');
        $this->assertGuest('juri');
    }

    public function test_institution_sifre_sifirlama_linki_institution_broker_uzerinden_gonderilir(): void
    {
        Notification::fake();

        $staff = $this->staff();

        $this->post(route('institution.password.email'), ['email' => $staff->email]);

        Notification::assertSentTo($staff, ResetPasswordNotification::class);
    }

    public function test_kurum_personeli_zorunlu_olarak_bir_kuruma_bagli(): void
    {
        $staff = $this->staff();

        $this->assertNotNull($staff->institution_id);
        $this->assertInstanceOf(Institution::class, $staff->institution);
    }

    /**
     * Regresyon testi: Laravel'in yerleşik `guest` middleware'i
     * (RedirectIfAuthenticated) zaten oturum açmış bir kullanıcıyı login/
     * register'dan uzaklaştırırken varsayılan olarak bare `dashboard` route'unu
     * arıyor — guard'dan bağımsız. Bu, institution guard'ında oturum açık bir
     * kullanıcı /login'e gittiğinde yanlışlıkla Üye'nin (web guard) dashboard/
     * login akışına düşmesine yol açıyordu (bkz. AppServiceProvider'daki
     * RedirectIfAuthenticated::redirectUsing() düzeltmesi).
     */
    public function test_giris_yapmis_kurum_personeli_login_sayfasina_giderse_kendi_paneline_yonlenir(): void
    {
        $staff = $this->staff();

        $response = $this->actingAs($staff, 'institution')->get(route('institution.login'));

        $response->assertRedirect(route('institution.dashboard'));
    }
}
