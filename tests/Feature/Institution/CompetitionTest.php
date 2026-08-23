<?php

namespace Tests\Feature\Institution;

use App\Enums\CompetitionAudience;
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

    public function test_kurum_bilgileri_eksikse_yeni_yarisma_olusturulamaz(): void
    {
        $institution = Institution::factory()->create([
            'name' => null,
            'email' => null,
            'phone' => null,
        ]);
        $staff = InstitutionStaff::factory()->for($institution)->create();

        $response = $this->actingAs($staff, 'institution')->post(route('institution.competitions.store'));

        $response->assertRedirect(route('institution.competitions.index'));
        $this->assertDatabaseCount('competitions', 0);
    }

    public function test_kurum_bilgileri_eksikse_yeni_basvuru_yerine_tamamlama_baglantisi_gosterilir(): void
    {
        $institution = Institution::factory()->create(['phone' => null]);
        $staff = InstitutionStaff::factory()->for($institution)->create();

        $response = $this->actingAs($staff, 'institution')->get(route('institution.competitions.index'));

        $response->assertOk();
        $response->assertSee(__('institution.competitions.incomplete_profile_title'));
        $response->assertSee(__('institution.competitions.complete_profile'));
        $response->assertDontSee(__('institution.competitions.add_new'));
    }

    public function test_adim_1_yarisma_kitlesi_secilmeden_sonraki_adima_gecilemez(): void
    {
        $staff = $this->staff();
        $competition = Competition::factory()->for($staff->institution)->for($staff)->create(['audience' => null]);

        $response = $this->actingAs($staff, 'institution')->put(
            route('institution.competitions.step.update', [$competition, 1]),
            ['action' => 'next']
        );

        $response->assertSessionHasErrors('audience');
        $this->assertSame(1, $competition->fresh()->current_step);
    }

    public function test_adim_1_uluslararasi_kitle_secilebilir_ve_adim_2ye_gecilir(): void
    {
        $staff = $this->staff();
        $competition = Competition::factory()->for($staff->institution)->for($staff)->create(['audience' => null]);

        $response = $this->actingAs($staff, 'institution')->put(
            route('institution.competitions.step.update', [$competition, 1]),
            ['audience' => 'international', 'action' => 'next']
        );

        $competition->refresh();

        $response->assertRedirect(route('institution.competitions.step.show', [$competition, 2]));
        $this->assertSame(CompetitionAudience::International, $competition->audience);
        $this->assertTrue($competition->requiresEnglishContent());
        $this->assertSame(2, $competition->current_step);
    }

    public function test_adim_1_kitle_seceneklerini_aciklama_ve_tanimlariyla_gosterir(): void
    {
        $staff = $this->staff();
        $competition = Competition::factory()->for($staff->institution)->for($staff)->create(['audience' => null]);

        $response = $this->actingAs($staff, 'institution')->get(
            route('institution.competitions.step.show', [$competition, 1])
        );

        $response->assertOk();
        $response->assertSee('id="audience_national"', false);
        $response->assertSee('id="audience_international"', false);
        $response->assertSee(__('institution.competitions.audiences.national.description'));
        $response->assertSee(__('institution.competitions.audiences.national.definition'));
        $response->assertSee(__('institution.competitions.audiences.international.description'));
        $response->assertSee(__('institution.competitions.audiences.international.definition'));
        $response->assertSee('aria-controls="field-help-audience"', false);
    }

    public function test_adim_2_zorunlu_alanlar_doldurulmadan_sonraki_adima_gecilemez(): void
    {
        $staff = $this->staff();
        $competition = Competition::factory()->for($staff->institution)->for($staff)->create([
            'name' => null,
            'partners' => null,
            'subject' => null,
            'purpose' => null,
            'current_step' => 2,
        ]);

        $response = $this->actingAs($staff, 'institution')->put(
            route('institution.competitions.step.update', [$competition, 2]),
            ['name' => 'Test Yarışması', 'action' => 'next']
        );

        $response->assertSessionHasErrors(['subject', 'purpose']);
        $response->assertSessionDoesntHaveErrors('partners');
        $this->assertSame(2, $competition->fresh()->current_step);
    }

    public function test_adim_2_taslak_olarak_kismi_veriyle_kaydedilebilir(): void
    {
        $staff = $this->staff();
        $competition = Competition::factory()->for($staff->institution)->for($staff)->create(['name' => null, 'current_step' => 2]);

        $response = $this->actingAs($staff, 'institution')->put(
            route('institution.competitions.step.update', [$competition, 2]),
            ['name' => 'Test Yarışması', 'action' => 'draft']
        );

        $response->assertSessionDoesntHaveErrors();
        $this->assertSame('Test Yarışması', $competition->fresh()->name);
        $this->assertSame(2, $competition->fresh()->current_step);
    }

    public function test_adim_2_duzenleyen_kurumu_salt_okunur_ve_metin_sinirlarini_gosterir(): void
    {
        $staff = $this->staff();
        $competition = Competition::factory()->for($staff->institution)->for($staff)->create([
            'subject' => null,
            'purpose' => null,
            'current_step' => 2,
        ]);

        $response = $this->actingAs($staff, 'institution')->get(
            route('institution.competitions.step.show', [$competition, 2])
        );

        $response->assertOk();
        $response->assertSee($staff->institution->name);
        $response->assertSee('id="organizing_institution"', false);
        $response->assertSee('readonly', false);
        $response->assertSee('id="partners"', false);
        $response->assertSee('maxlength="1000"', false);
        $response->assertSee('x-on:input="remaining = Math.max(0, max - [...$event.target.value].length)"', false);
        $response->assertSee(__('institution.competitions.fields.characters_remaining', ['remaining' => 1000, 'max' => 1000]));
        $response->assertSee('aria-controls="field-help-name"', false);
        $response->assertSee('aria-controls="field-help-organizing_institution"', false);
        $response->assertSee('aria-controls="field-help-partners"', false);
        $response->assertSee('aria-controls="field-help-subject"', false);
        $response->assertSee('aria-controls="field-help-purpose"', false);
        $response->assertSee(__('institution.competitions.field_help.subject.example'));
    }

    public function test_adim_2_tamamlaninca_adim_3e_gecilir_ve_current_step_ilerler(): void
    {
        $staff = $this->staff();
        $competition = Competition::factory()->for($staff->institution)->for($staff)->create(['current_step' => 2]);

        $response = $this->actingAs($staff, 'institution')->put(
            route('institution.competitions.step.update', [$competition, 2]),
            [
                'name' => 'Test Yarışması',
                'partners' => 'Test Paydaş',
                'subject' => 'Test Konu',
                'purpose' => 'Test Amaç',
                'action' => 'next',
            ]
        );

        $response->assertRedirect(route('institution.competitions.step.show', [$competition, 3]));
        $this->assertSame(3, $competition->fresh()->current_step);
    }

    public function test_adim_2_paydas_girilmeden_tamamlanabilir(): void
    {
        $staff = $this->staff();
        $competition = Competition::factory()->for($staff->institution)->for($staff)->create(['current_step' => 2]);

        $response = $this->actingAs($staff, 'institution')->put(
            route('institution.competitions.step.update', [$competition, 2]),
            [
                'name' => 'Test Yarışması',
                'partners' => '',
                'subject' => 'Test Konu',
                'purpose' => 'Test Amaç',
                'action' => 'next',
            ]
        );

        $response->assertSessionDoesntHaveErrors();
        $response->assertRedirect(route('institution.competitions.step.show', [$competition, 3]));
        $this->assertNull($competition->fresh()->partners);
    }

    public function test_adim_2_konu_ve_amac_1000_karakteri_gecemez(): void
    {
        $staff = $this->staff();
        $competition = Competition::factory()->for($staff->institution)->for($staff)->create(['current_step' => 2]);

        $response = $this->actingAs($staff, 'institution')->put(
            route('institution.competitions.step.update', [$competition, 2]),
            [
                'name' => 'Test Yarışması',
                'subject' => str_repeat('a', 1001),
                'purpose' => str_repeat('b', 1001),
                'action' => 'next',
            ]
        );

        $response->assertSessionHasErrors(['subject', 'purpose']);
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

    public function test_adim_2_tamamlanmadan_onaya_gonderilemez(): void
    {
        $staff = $this->staff();
        $competition = Competition::factory()->for($staff->institution)->for($staff)->create(['subject' => null]);

        $response = $this->actingAs($staff, 'institution')->post(route('institution.competitions.submit', $competition));

        $response->assertStatus(422);
        $this->assertSame(CompetitionStatus::Draft, $competition->fresh()->status);
    }

    public function test_yarisma_kitlesi_secilmeden_onaya_gonderilemez(): void
    {
        $staff = $this->staff();
        $competition = Competition::factory()->for($staff->institution)->for($staff)->create(['audience' => null]);

        $response = $this->actingAs($staff, 'institution')->post(route('institution.competitions.submit', $competition));

        $response->assertStatus(422);
        $this->assertSame(CompetitionStatus::Draft, $competition->fresh()->status);
    }

    public function test_tamamlanan_adimlar_onaya_gonderilebilir_ve_submitted_log_yazilir(): void
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
