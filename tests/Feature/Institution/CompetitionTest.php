<?php

namespace Tests\Feature\Institution;

use App\Enums\CompetitionAudience;
use App\Enums\CompetitionInfrastructureProvider;
use App\Enums\CompetitionStatus;
use App\Models\Competition;
use App\Models\CompetitionType;
use App\Models\Institution;
use App\Models\InstitutionStaff;
use Database\Seeders\CompetitionTypeSeeder;
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
        $competition = Competition::factory()
            ->for($staff->institution)
            ->for($staff)
            ->withTranslations(['tr' => ['name' => null, 'subject' => null, 'purpose' => null]])
            ->create(['partners' => null, 'current_step' => 2]);

        $response = $this->actingAs($staff, 'institution')->put(
            route('institution.competitions.step.update', [$competition, 2]),
            ['tr' => ['name' => 'Test Yarışması'], 'action' => 'next']
        );

        $response->assertSessionHasErrors(['tr.subject', 'tr.purpose']);
        $response->assertSessionDoesntHaveErrors('partners');
        $this->assertSame(2, $competition->fresh()->current_step);
    }

    public function test_adim_2_uluslararasi_yarismada_ingilizce_alanlar_zorunludur(): void
    {
        $staff = $this->staff();
        $competition = Competition::factory()->for($staff->institution)->for($staff)->create([
            'audience' => CompetitionAudience::International,
            'current_step' => 2,
        ]);

        $response = $this->actingAs($staff, 'institution')->put(
            route('institution.competitions.step.update', [$competition, 2]),
            [
                'tr' => ['name' => 'Test Yarışması', 'subject' => 'Test Konu', 'purpose' => 'Test Amaç'],
                'action' => 'next',
            ]
        );

        $response->assertSessionHasErrors(['en.name', 'en.subject', 'en.purpose']);
        $this->assertSame(2, $competition->fresh()->current_step);
    }

    public function test_adim_2_uluslararasi_yarismada_turkce_ve_ingilizce_icerikler_kaydedilir(): void
    {
        $staff = $this->staff();
        $competition = Competition::factory()->for($staff->institution)->for($staff)->create([
            'audience' => CompetitionAudience::International,
            'current_step' => 2,
        ]);

        $response = $this->actingAs($staff, 'institution')->put(
            route('institution.competitions.step.update', [$competition, 2]),
            [
                'tr' => ['name' => 'Doğa Yarışması', 'subject' => 'Doğa', 'purpose' => 'Farkındalık'],
                'en' => ['name' => 'Nature Competition', 'subject' => 'Nature', 'purpose' => 'Awareness'],
                'action' => 'next',
            ]
        );

        $response->assertRedirect(route('institution.competitions.step.show', [$competition, 3]));
        $this->assertSame('Doğa Yarışması', $competition->fresh()->getTranslation('tr', false)?->name);
        $this->assertSame('Nature Competition', $competition->fresh()->getTranslation('en', false)?->name);
    }

    public function test_adim_2_taslak_olarak_kismi_veriyle_kaydedilebilir(): void
    {
        $staff = $this->staff();
        $competition = Competition::factory()
            ->for($staff->institution)
            ->for($staff)
            ->withTranslations(['tr' => ['name' => null, 'subject' => null, 'purpose' => null]])
            ->create(['current_step' => 2]);

        $response = $this->actingAs($staff, 'institution')->put(
            route('institution.competitions.step.update', [$competition, 2]),
            ['tr' => ['name' => 'Test Yarışması'], 'action' => 'draft']
        );

        $response->assertSessionDoesntHaveErrors();
        $this->assertSame('Test Yarışması', $competition->fresh()->getTranslation('tr', false)?->name);
        $this->assertSame(2, $competition->fresh()->current_step);
    }

    public function test_adim_2_duzenleyen_kurumu_salt_okunur_ve_metin_sinirlarini_gosterir(): void
    {
        $staff = $this->staff();
        $competition = Competition::factory()
            ->for($staff->institution)
            ->for($staff)
            ->withTranslations([
                'tr' => ['subject' => null, 'purpose' => null],
                'en' => ['subject' => null, 'purpose' => null],
            ])
            ->create(['current_step' => 2]);

        $response = $this->actingAs($staff, 'institution')->get(
            route('institution.competitions.step.show', [$competition, 2])
        );

        $response->assertOk();
        $response->assertSee($staff->institution->name);
        $response->assertSee('id="organizing_institution"', false);
        $response->assertSee('readonly', false);
        $response->assertSee('id="partners"', false);
        $response->assertSee('id="language-tab-tr"', false);
        $response->assertSee('id="language-tab-en"', false);
        $response->assertSee('id="tr_name"', false);
        $response->assertSee('id="en_name"', false);
        $response->assertSee('maxlength="1000"', false);
        $response->assertSee('x-on:input="remaining = Math.max(0, max - [...$event.target.value].length)"', false);
        $response->assertSee(__('institution.competitions.fields.characters_remaining', ['remaining' => 1000, 'max' => 1000]));
        $response->assertSee('aria-controls="field-help-tr_name"', false);
        $response->assertSee('aria-controls="field-help-en_name"', false);
        $response->assertSee('aria-controls="field-help-organizing_institution"', false);
        $response->assertSee('aria-controls="field-help-partners"', false);
        $response->assertSee('aria-controls="field-help-tr_subject"', false);
        $response->assertSee('aria-controls="field-help-tr_purpose"', false);
        $response->assertSee(__('institution.competitions.field_help.subject.example'));
    }

    public function test_adim_2_tamamlaninca_adim_3e_gecilir_ve_current_step_ilerler(): void
    {
        $staff = $this->staff();
        $competition = Competition::factory()->for($staff->institution)->for($staff)->create(['current_step' => 2]);

        $response = $this->actingAs($staff, 'institution')->put(
            route('institution.competitions.step.update', [$competition, 2]),
            [
                'partners' => 'Test Paydaş',
                'tr' => [
                    'name' => 'Test Yarışması',
                    'subject' => 'Test Konu',
                    'purpose' => 'Test Amaç',
                ],
                'action' => 'next',
            ]
        );

        $response->assertRedirect(route('institution.competitions.step.show', [$competition, 3]));
        $this->assertDatabaseHas('competition_translations', [
            'competition_id' => $competition->id,
            'locale' => 'tr',
            'name' => 'Test Yarışması',
            'subject' => 'Test Konu',
            'purpose' => 'Test Amaç',
        ]);
        $this->assertSame(3, $competition->fresh()->current_step);
    }

    public function test_adim_2_paydas_girilmeden_tamamlanabilir(): void
    {
        $staff = $this->staff();
        $competition = Competition::factory()->for($staff->institution)->for($staff)->create(['current_step' => 2]);

        $response = $this->actingAs($staff, 'institution')->put(
            route('institution.competitions.step.update', [$competition, 2]),
            [
                'partners' => '',
                'tr' => [
                    'name' => 'Test Yarışması',
                    'subject' => 'Test Konu',
                    'purpose' => 'Test Amaç',
                ],
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
                'tr' => [
                    'name' => 'Test Yarışması',
                    'subject' => str_repeat('a', 1001),
                    'purpose' => str_repeat('b', 1001),
                ],
                'action' => 'next',
            ]
        );

        $response->assertSessionHasErrors(['tr.subject', 'tr.purpose']);
        $this->assertSame(2, $competition->fresh()->current_step);
    }

    public function test_adim_3_alt_yapi_secilmeden_sonraki_adima_gecilemez(): void
    {
        $staff = $this->staff();
        $competition = Competition::factory()->for($staff->institution)->for($staff)->create([
            'infrastructure_provider' => null,
            'competition_type_id' => null,
            'current_step' => 3,
        ]);

        $response = $this->actingAs($staff, 'institution')->put(
            route('institution.competitions.step.update', [$competition, 3]),
            ['action' => 'next']
        );

        $response->assertSessionHasErrors('infrastructure_provider');
        $this->assertSame(3, $competition->fresh()->current_step);
    }

    public function test_adim_3_tfsf_alt_yapisi_secilir_ve_adim_4e_gecilir(): void
    {
        $staff = $this->staff();
        $competition = Competition::factory()->for($staff->institution)->for($staff)->create([
            'infrastructure_provider' => null,
            'current_step' => 3,
        ]);

        $response = $this->actingAs($staff, 'institution')->put(
            route('institution.competitions.step.update', [$competition, 3]),
            ['infrastructure_provider' => 'tfsf', 'action' => 'next']
        );

        $competition->refresh();

        $response->assertRedirect(route('institution.competitions.step.show', [$competition, 4]));
        $this->assertSame(CompetitionInfrastructureProvider::Tfsf, $competition->infrastructure_provider);
        $this->assertSame(4, $competition->current_step);
    }

    public function test_adim_3_harici_alt_yapi_secilince_adim_4_atlanir(): void
    {
        $staff = $this->staff();
        $competition = Competition::factory()->for($staff->institution)->for($staff)->create([
            'infrastructure_provider' => null,
            'competition_type_id' => null,
            'current_step' => 3,
        ]);

        $response = $this->actingAs($staff, 'institution')->put(
            route('institution.competitions.step.update', [$competition, 3]),
            ['infrastructure_provider' => 'external', 'action' => 'next']
        );

        $competition->refresh();

        $response->assertRedirect(route('institution.competitions.step.show', [$competition, 5]));
        $this->assertSame(CompetitionInfrastructureProvider::External, $competition->infrastructure_provider);
        $this->assertNull($competition->competition_type_id);
        $this->assertSame(5, $competition->current_step);

        $stepFour = $this->actingAs($staff, 'institution')->get(
            route('institution.competitions.step.show', [$competition, 4])
        );
        $stepFour->assertRedirect(route('institution.competitions.step.show', [$competition, 5]));

        $stepFive = $this->actingAs($staff, 'institution')->get(
            route('institution.competitions.step.show', [$competition, 5])
        );
        $stepFive->assertDontSee(__('institution.competitions.steps.4.label'));
    }

    public function test_adim_3_iki_alt_yapi_secenegini_ve_tfsf_hizmetlerini_gosterir(): void
    {
        $staff = $this->staff();
        $competition = Competition::factory()->for($staff->institution)->for($staff)->create([
            'current_step' => 3,
        ]);

        $response = $this->actingAs($staff, 'institution')->get(
            route('institution.competitions.step.show', [$competition, 3])
        );

        $response->assertOk();
        $response->assertSee('id="infrastructure_provider_tfsf"', false);
        $response->assertSee('id="infrastructure_provider_external"', false);
        $response->assertSee(__('institution.competitions.infrastructure_providers.tfsf.title'));
        $response->assertSee(__('institution.competitions.infrastructure_providers.external.title'));
        $response->assertSee('aria-controls="field-help-infrastructure_provider"', false);

        foreach (__('institution.competitions.infrastructure_providers.tfsf.services') as $service) {
            $response->assertSee($service);
        }
    }

    public function test_adim_4_seed_edilen_yarisma_turlerini_aciklamalariyla_gosterir(): void
    {
        $this->seed(CompetitionTypeSeeder::class);
        $staff = $this->staff();
        $competition = Competition::factory()->for($staff->institution)->for($staff)->create([
            'competition_type_id' => null,
            'current_step' => 4,
        ]);

        $response = $this->actingAs($staff, 'institution')->get(
            route('institution.competitions.step.show', [$competition, 4])
        );

        $response->assertOk();
        $response->assertSee('aria-controls="field-help-competition_type"', false);

        foreach (CompetitionType::active()->with('translations')->get() as $competitionType) {
            $response->assertSee($competitionType->name);
            $response->assertSee($competitionType->description);
        }
    }

    public function test_adim_4_yarisma_turu_secilmeden_sonraki_adima_gecilemez(): void
    {
        $staff = $this->staff();
        $competition = Competition::factory()->for($staff->institution)->for($staff)->create([
            'competition_type_id' => null,
            'current_step' => 4,
        ]);

        $response = $this->actingAs($staff, 'institution')->put(
            route('institution.competitions.step.update', [$competition, 4]),
            ['action' => 'next']
        );

        $response->assertSessionHasErrors('competition_type');
        $this->assertSame(4, $competition->fresh()->current_step);
    }

    public function test_adim_4_aktif_yarisma_turu_secilir_ve_adim_5e_gecilir(): void
    {
        $staff = $this->staff();
        $competitionType = CompetitionType::factory()->create();
        $competition = Competition::factory()->for($staff->institution)->for($staff)->create([
            'competition_type_id' => null,
            'current_step' => 4,
        ]);

        $response = $this->actingAs($staff, 'institution')->put(
            route('institution.competitions.step.update', [$competition, 4]),
            ['competition_type' => $competitionType->id, 'action' => 'next']
        );

        $competition->refresh();

        $response->assertRedirect(route('institution.competitions.step.show', [$competition, 5]));
        $this->assertTrue($competition->competitionType->is($competitionType));
        $this->assertSame(5, $competition->current_step);
    }

    public function test_adim_4_pasif_yarisma_turu_secilemez(): void
    {
        $staff = $this->staff();
        $competitionType = CompetitionType::factory()->create(['status' => false]);
        $competition = Competition::factory()->for($staff->institution)->for($staff)->create([
            'competition_type_id' => null,
            'current_step' => 4,
        ]);

        $response = $this->actingAs($staff, 'institution')->put(
            route('institution.competitions.step.update', [$competition, 4]),
            ['competition_type' => $competitionType->id, 'action' => 'next']
        );

        $response->assertSessionHasErrors('competition_type');
        $this->assertNull($competition->fresh()->competition_type_id);
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
        $competition = Competition::factory()
            ->for($staff->institution)
            ->for($staff)
            ->withTranslations(['tr' => ['subject' => null]])
            ->create();

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

    public function test_yarisma_alt_yapisi_secilmeden_onaya_gonderilemez(): void
    {
        $staff = $this->staff();
        $competition = Competition::factory()->for($staff->institution)->for($staff)->create([
            'infrastructure_provider' => null,
        ]);

        $response = $this->actingAs($staff, 'institution')->post(route('institution.competitions.submit', $competition));

        $response->assertStatus(422);
        $this->assertSame(CompetitionStatus::Draft, $competition->fresh()->status);
    }

    public function test_tfsf_alt_yapisinda_yarisma_turu_secilmeden_onaya_gonderilemez(): void
    {
        $staff = $this->staff();
        $competition = Competition::factory()->for($staff->institution)->for($staff)->create([
            'competition_type_id' => null,
        ]);

        $response = $this->actingAs($staff, 'institution')->post(route('institution.competitions.submit', $competition));

        $response->assertStatus(422);
        $this->assertSame(CompetitionStatus::Draft, $competition->fresh()->status);
    }

    public function test_harici_alt_yapida_yarisma_turu_secilmeden_onaya_gonderilebilir(): void
    {
        $staff = $this->staff();
        $competition = Competition::factory()->for($staff->institution)->for($staff)->create([
            'infrastructure_provider' => CompetitionInfrastructureProvider::External,
            'competition_type_id' => null,
        ]);

        $response = $this->actingAs($staff, 'institution')->post(route('institution.competitions.submit', $competition));

        $response->assertRedirect(route('institution.competitions.index'));
        $this->assertSame(CompetitionStatus::PendingReview, $competition->fresh()->status);
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
