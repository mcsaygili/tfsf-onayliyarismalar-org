<?php

namespace Tests\Feature\Institution;

use App\Models\Institution;
use App\Models\InstitutionStaff;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_kurum_bilgileri_sayfasi_dogrulanmis_personel_icin_goruntulenebilir(): void
    {
        $institution = Institution::factory()->create();
        $staff = InstitutionStaff::factory()->for($institution)->create();

        $response = $this->actingAs($staff, 'institution')->get(route('institution.profile.edit'));

        $response->assertOk();
    }

    public function test_kurum_bilgileri_guncellenebilir(): void
    {
        $institution = Institution::factory()->create(['name' => null]);
        $staff = InstitutionStaff::factory()->for($institution)->create();

        $response = $this->actingAs($staff, 'institution')->patch(route('institution.profile.update'), [
            'institution_name' => 'Örnek Üniversitesi',
            'institution_email' => 'info@ornek-universite.edu.tr',
            'institution_phone' => '+90 212 000 00 00',
            'institution_website' => 'https://ornek-universite.edu.tr',
            'institution_address' => 'İstanbul',
        ]);

        $response->assertRedirect(route('institution.profile.edit'));

        $this->assertSame('Örnek Üniversitesi', $institution->fresh()->name);
    }

    public function test_kurum_adi_bos_birakilamaz(): void
    {
        $institution = Institution::factory()->create();
        $staff = InstitutionStaff::factory()->for($institution)->create();

        $response = $this->actingAs($staff, 'institution')->patch(route('institution.profile.update'), [
            'institution_name' => '',
        ]);

        $response->assertSessionHasErrors('institution_name');
    }

    public function test_kurum_e_postasi_ve_telefonu_zorunludur(): void
    {
        $institution = Institution::factory()->create();
        $staff = InstitutionStaff::factory()->for($institution)->create();

        $response = $this->actingAs($staff, 'institution')->patch(route('institution.profile.update'), [
            'institution_name' => 'Örnek Üniversitesi',
            'institution_email' => '',
            'institution_phone' => '',
        ]);

        $response->assertSessionHasErrors(['institution_email', 'institution_phone']);
    }

    public function test_dogrulanmamis_personel_kurum_bilgileri_sayfasina_erisemez(): void
    {
        $institution = Institution::factory()->create();
        $staff = InstitutionStaff::factory()->for($institution)->unverified()->create();

        $response = $this->actingAs($staff, 'institution')->get(route('institution.profile.edit'));

        $response->assertRedirect(route('institution.verification.notice'));
    }

    public function test_sifre_islemleri_sayfasi_dogrulanmis_personel_icin_goruntulenebilir(): void
    {
        $institution = Institution::factory()->create();
        $staff = InstitutionStaff::factory()->for($institution)->create();

        $response = $this->actingAs($staff, 'institution')->get(route('institution.password.edit'));

        $response->assertOk();
    }

    public function test_personel_sifresini_guncelleyebilir(): void
    {
        $institution = Institution::factory()->create();
        $staff = InstitutionStaff::factory()->for($institution)->create();

        $response = $this->actingAs($staff, 'institution')->from(route('institution.password.edit'))->put(route('institution.password.update'), [
            'current_password' => 'password',
            'password' => 'yeni-guclu-sifre',
            'password_confirmation' => 'yeni-guclu-sifre',
        ]);

        $response->assertRedirect(route('institution.password.edit'));
        $this->assertTrue(Hash::check('yeni-guclu-sifre', $staff->fresh()->password));
    }

    public function test_yanlis_mevcut_sifreyle_personel_sifresi_guncellenemez(): void
    {
        $institution = Institution::factory()->create();
        $staff = InstitutionStaff::factory()->for($institution)->create();

        $response = $this->actingAs($staff, 'institution')->put(route('institution.password.update'), [
            'current_password' => 'yanlis-sifre',
            'password' => 'yeni-guclu-sifre',
            'password_confirmation' => 'yeni-guclu-sifre',
        ]);

        $response->assertSessionHasErrorsIn('updatePassword', ['current_password']);
    }

    public function test_dogrulanmamis_personel_sifre_sayfasina_erisemez(): void
    {
        $institution = Institution::factory()->create();
        $staff = InstitutionStaff::factory()->for($institution)->unverified()->create();

        $response = $this->actingAs($staff, 'institution')->get(route('institution.password.edit'));

        $response->assertRedirect(route('institution.verification.notice'));
    }
}
