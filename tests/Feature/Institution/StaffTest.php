<?php

namespace Tests\Feature\Institution;

use App\Models\Institution;
use App\Models\InstitutionStaff;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffTest extends TestCase
{
    use RefreshDatabase;

    public function test_yetkili_listesi_sadece_kendi_kurumunun_personelini_gosterir(): void
    {
        $institution = Institution::factory()->create();
        $staff = InstitutionStaff::factory()->for($institution)->create();

        $otherInstitution = Institution::factory()->create();
        InstitutionStaff::factory()->for($otherInstitution)->create();

        $response = $this->actingAs($staff, 'institution')->get(route('institution.staff.index'));

        $response->assertOk();
        $response->assertViewHas('staffList', function ($staffList) use ($staff) {
            return $staffList->total() === 1 && $staffList->first()->is($staff);
        });
    }

    public function test_yetkili_listesi_sayfalanir(): void
    {
        $institution = Institution::factory()->create();
        $staff = InstitutionStaff::factory()->for($institution)->create();
        InstitutionStaff::factory()->for($institution)->count(12)->create();

        $response = $this->actingAs($staff, 'institution')->get(route('institution.staff.index'));

        $response->assertOk();
        $response->assertViewHas('staffList', function ($staffList) {
            return $staffList->total() === 13 && $staffList->lastPage() === 2;
        });
    }

    public function test_yeni_yetkili_eklenebilir(): void
    {
        $institution = Institution::factory()->create();
        $staff = InstitutionStaff::factory()->for($institution)->create();

        $response = $this->actingAs($staff, 'institution')->post(route('institution.staff.store'), [
            'email' => 'yeni.yetkili@ornek-universite.edu.tr',
            'first_name' => 'Ayşe',
            'last_name' => 'Yılmaz',
            'phone' => '+90 555 000 00 00',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertRedirect(route('institution.staff.index'));

        $this->assertSame(2, $institution->staff()->count());

        $newStaff = InstitutionStaff::where('email', 'yeni.yetkili@ornek-universite.edu.tr')->first();
        $this->assertNotNull($newStaff);
        $this->assertSame($institution->id, $newStaff->institution_id);
        $this->assertNotNull($newStaff->email_verified_at);
    }

    public function test_kendi_kurumuna_ait_yetkili_duzenlenebilir(): void
    {
        $institution = Institution::factory()->create();
        $staff = InstitutionStaff::factory()->for($institution)->create();
        $colleague = InstitutionStaff::factory()->for($institution)->create(['first_name' => 'Eski']);

        $response = $this->actingAs($staff, 'institution')->patch(route('institution.staff.update', $colleague), [
            'email' => $colleague->email,
            'first_name' => 'Yeni',
            'last_name' => $colleague->last_name,
            'status' => 0,
        ]);

        $response->assertRedirect(route('institution.staff.index'));

        $this->assertSame('Yeni', $colleague->fresh()->first_name);
        $this->assertFalse($colleague->fresh()->status);
    }

    public function test_baska_kurumun_yetkilisi_duzenlenemez(): void
    {
        $institution = Institution::factory()->create();
        $staff = InstitutionStaff::factory()->for($institution)->create();

        $otherInstitution = Institution::factory()->create();
        $otherStaff = InstitutionStaff::factory()->for($otherInstitution)->create();

        $this->actingAs($staff, 'institution')->get(route('institution.staff.edit', $otherStaff))->assertForbidden();

        $this->actingAs($staff, 'institution')->patch(route('institution.staff.update', $otherStaff), [
            'email' => $otherStaff->email,
            'status' => 0,
        ])->assertForbidden();
    }

    public function test_dogrulanmamis_personel_yetkili_listesine_erisemez(): void
    {
        $institution = Institution::factory()->create();
        $staff = InstitutionStaff::factory()->for($institution)->unverified()->create();

        $response = $this->actingAs($staff, 'institution')->get(route('institution.staff.index'));

        $response->assertRedirect(route('institution.verification.notice'));
    }
}
