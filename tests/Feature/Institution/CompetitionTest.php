<?php

namespace Tests\Feature\Institution;

use App\Enums\CompetitionStatus;
use App\Models\Competition;
use App\Models\Institution;
use App\Models\InstitutionStaff;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompetitionTest extends TestCase
{
    use RefreshDatabase;

    private function staff(): InstitutionStaff
    {
        $institution = Institution::factory()->create();

        return InstitutionStaff::factory()->for($institution)->create();
    }

    public function test_yeni_taslak_basvuru_olusturulabilir(): void
    {
        $staff = $this->staff();

        $response = $this->actingAs($staff, 'institution')->post(route('institution.competitions.store'));

        $competition = Competition::query()->firstOrFail();

        $this->assertSame($staff->institution_id, $competition->institution_id);
        $this->assertSame($staff->id, $competition->institution_staff_id);
        $this->assertSame(CompetitionStatus::Draft, $competition->status);
        $response->assertRedirect(route('institution.competitions.step.show', [$competition, 1]));
    }

    public function test_adim_1_zorunlu_alanlar_doldurulmadan_sonraki_adima_gecilemez(): void
    {
        $staff = $this->staff();
        $competition = Competition::factory()->for($staff->institution)->for($staff)->create([
            'name' => null,
            'partners' => null,
            'subject' => null,
            'purpose' => null,
        ]);

        $response = $this->actingAs($staff, 'institution')->put(
            route('institution.competitions.step.update', [$competition, 1]),
            ['name' => 'Test Yarışması', 'action' => 'next']
        );

        $response->assertSessionHasErrors(['partners', 'subject', 'purpose']);
        $this->assertSame(1, $competition->fresh()->current_step);
    }

    public function test_adim_1_taslak_olarak_kismi_veriyle_kaydedilebilir(): void
    {
        $staff = $this->staff();
        $competition = Competition::factory()->for($staff->institution)->for($staff)->create(['name' => null]);

        $response = $this->actingAs($staff, 'institution')->put(
            route('institution.competitions.step.update', [$competition, 1]),
            ['name' => 'Test Yarışması', 'action' => 'draft']
        );

        $response->assertSessionDoesntHaveErrors();
        $this->assertSame('Test Yarışması', $competition->fresh()->name);
        $this->assertSame(1, $competition->fresh()->current_step);
    }

    public function test_adim_1_tamamlaninca_adim_2ye_gecilir_ve_current_step_ilerler(): void
    {
        $staff = $this->staff();
        $competition = Competition::factory()->for($staff->institution)->for($staff)->create();

        $response = $this->actingAs($staff, 'institution')->put(
            route('institution.competitions.step.update', [$competition, 1]),
            [
                'name' => 'Test Yarışması',
                'partners' => 'Test Paydaş',
                'subject' => 'Test Konu',
                'purpose' => 'Test Amaç',
                'action' => 'next',
            ]
        );

        $response->assertRedirect(route('institution.competitions.step.show', [$competition, 2]));
        $this->assertSame(2, $competition->fresh()->current_step);
    }

    public function test_baska_kurumun_basvurusuna_erisilemez(): void
    {
        $staff = $this->staff();
        $otherCompetition = Competition::factory()->create();

        $response = $this->actingAs($staff, 'institution')->get(
            route('institution.competitions.step.show', [$otherCompetition, 1])
        );

        $response->assertForbidden();
    }

    public function test_adim_1_tamamlanmadan_onaya_gonderilemez(): void
    {
        $staff = $this->staff();
        $competition = Competition::factory()->for($staff->institution)->for($staff)->create(['subject' => null]);

        $response = $this->actingAs($staff, 'institution')->post(route('institution.competitions.submit', $competition));

        $response->assertStatus(422);
        $this->assertSame(CompetitionStatus::Draft, $competition->fresh()->status);
    }

    public function test_adim_1_tamamlaninca_onaya_gonderilebilir_ve_submitted_log_yazilir(): void
    {
        $staff = $this->staff();
        $competition = Competition::factory()->for($staff->institution)->for($staff)->create();

        $response = $this->actingAs($staff, 'institution')->post(route('institution.competitions.submit', $competition));

        $competition->refresh();

        $response->assertRedirect(route('institution.competitions.index'));
        $this->assertSame(CompetitionStatus::PendingReview, $competition->status);
        $this->assertNotNull($competition->submitted_at);
        $this->assertSame(1, $competition->statusLogs()->count());
        $this->assertSame('submitted', $competition->statusLogs()->first()->action);
    }

    public function test_onaylanan_basvuru_duzenlenemez(): void
    {
        $staff = $this->staff();
        $competition = Competition::factory()->for($staff->institution)->for($staff)->create([
            'status' => CompetitionStatus::Approved,
        ]);

        $response = $this->actingAs($staff, 'institution')->put(
            route('institution.competitions.step.update', [$competition, 1]),
            ['action' => 'draft', 'name' => 'Değişiklik']
        );

        $response->assertForbidden();
    }
}
