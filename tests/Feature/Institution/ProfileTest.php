<?php

namespace Tests\Feature\Institution;

use App\Models\Institution;
use App\Models\InstitutionStaff;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_hesabim_sayfasi_dogrulanmis_personel_icin_goruntulenebilir(): void
    {
        $institution = Institution::factory()->create();
        $staff = InstitutionStaff::factory()->for($institution)->create();

        $response = $this->actingAs($staff, 'institution')->get(route('institution.profile.edit'));

        $response->assertOk();
    }

    public function test_kurum_ve_yetkili_bilgileri_guncellenebilir(): void
    {
        $institution = Institution::factory()->create(['name' => null]);
        $staff = InstitutionStaff::factory()->for($institution)->create(['first_name' => null, 'last_name' => null]);

        $response = $this->actingAs($staff, 'institution')->patch(route('institution.profile.update'), [
            'institution_name' => 'Örnek Üniversitesi',
            'institution_email' => 'info@ornek-universite.edu.tr',
            'institution_phone' => '+90 212 000 00 00',
            'institution_website' => 'https://ornek-universite.edu.tr',
            'institution_address' => 'İstanbul',
            'first_name' => 'Ayşe',
            'last_name' => 'Yılmaz',
            'phone' => '+90 555 000 00 00',
        ]);

        $response->assertRedirect(route('institution.profile.edit'));

        $this->assertSame('Örnek Üniversitesi', $institution->fresh()->name);
        $this->assertSame('Ayşe', $staff->fresh()->first_name);
        $this->assertSame('Yılmaz', $staff->fresh()->last_name);
    }

    public function test_kurum_adi_bos_birakilamaz(): void
    {
        $institution = Institution::factory()->create();
        $staff = InstitutionStaff::factory()->for($institution)->create();

        $response = $this->actingAs($staff, 'institution')->patch(route('institution.profile.update'), [
            'institution_name' => '',
            'first_name' => 'Ayşe',
            'last_name' => 'Yılmaz',
        ]);

        $response->assertSessionHasErrors('institution_name');
    }

    public function test_dogrulanmamis_personel_hesabim_sayfasina_erisemez(): void
    {
        $institution = Institution::factory()->create();
        $staff = InstitutionStaff::factory()->for($institution)->unverified()->create();

        $response = $this->actingAs($staff, 'institution')->get(route('institution.profile.edit'));

        $response->assertRedirect(route('institution.verification.notice'));
    }
}
