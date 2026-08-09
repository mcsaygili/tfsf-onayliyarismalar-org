<?php

namespace Tests\Feature\Institution;

use App\Models\Institution;
use App\Models\InstitutionStaff;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_gosterge_paneli_toplam_yetkili_sayisini_gosterir(): void
    {
        $institution = Institution::factory()->create();
        $staff = InstitutionStaff::factory()->for($institution)->create();
        InstitutionStaff::factory()->for($institution)->count(2)->create();

        $response = $this->actingAs($staff, 'institution')->get(route('institution.dashboard'));

        $response->assertOk();
        $response->assertViewHas('staffCount', 3);
        $response->assertSee('3');
    }

    public function test_kurum_bilgileri_eksikse_uyari_gosterilir(): void
    {
        $institution = Institution::factory()->create(['name' => null, 'email' => null, 'phone' => null]);
        $staff = InstitutionStaff::factory()->for($institution)->create();

        $response = $this->actingAs($staff, 'institution')->get(route('institution.dashboard'));

        $response->assertSee(__('institution.dashboard.incomplete_title'));
    }

    public function test_kurum_bilgileri_tamamsa_uyari_gosterilmez(): void
    {
        $institution = Institution::factory()->create([
            'name' => 'Örnek Üniversitesi',
            'email' => 'info@ornek-universite.edu.tr',
            'phone' => '+90 212 000 00 00',
        ]);
        $staff = InstitutionStaff::factory()->for($institution)->create();

        $response = $this->actingAs($staff, 'institution')->get(route('institution.dashboard'));

        $response->assertDontSee(__('institution.dashboard.incomplete_title'));
    }
}
