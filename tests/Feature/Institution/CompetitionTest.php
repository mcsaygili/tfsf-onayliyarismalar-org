<?php

namespace Tests\Feature\Institution;

use App\Enums\CompetitionAudience;
use App\Enums\CompetitionInfrastructureProvider;
use App\Enums\CompetitionStatus;
use App\Models\AgeEligibilityRule;
use App\Models\AwardReference;
use App\Models\CaptureDevice;
use App\Models\Competition;
use App\Models\CompetitionType;
use App\Models\Country;
use App\Models\Institution;
use App\Models\InstitutionStaff;
use App\Models\Juri;
use App\Models\MemberGroup;
use App\Models\ParticipantApprovalProcess;
use App\Models\ParticipantGender;
use App\Models\ProcessingMethod;
use Database\Seeders\AwardReferenceSeeder;
use Database\Seeders\CompetitionCategoryReferenceSeeder;
use Database\Seeders\CompetitionTypeSeeder;
use Database\Seeders\ParticipantApprovalProcessSeeder;
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

    private function completeCategoryStep(Competition $competition): void
    {
        $this->seed(CompetitionCategoryReferenceSeeder::class);
        $category = $competition->categories()->create(['sort_order' => 10, 'age_eligibility_rule_id' => AgeEligibilityRule::firstOrFail()->id]);
        $translations = ['tr' => ['name' => 'Genel']];
        if ($competition->requiresEnglishContent()) {
            $translations['en'] = ['name' => 'General'];
        }
        $category->upsertTranslations($translations);
        $category->genders()->sync([ParticipantGender::firstOrFail()->id]);
        $category->memberGroups()->sync([MemberGroup::firstOrFail()->id]);
        $category->captureDevices()->sync([CaptureDevice::firstOrFail()->id]);
        $category->processingMethods()->sync([ProcessingMethod::firstOrFail()->id]);
    }

    private function completeAwardStep(Competition $competition): void
    {
        $this->seed(AwardReferenceSeeder::class);
        foreach ($competition->categories as $category) {
            $category->awards()->create([
                'award_reference_id' => AwardReference::firstOrFail()->id,
                'quantity' => 1,
                'sort_order' => 10,
            ]);
        }
    }

    private function completeJurorStep(Competition $competition): void
    {
        $juror = Juri::factory()->create();
        foreach ($competition->categories as $category) {
            $category->jurorAssignments()->create([
                'juror_id' => $juror->id,
                'assigned_by' => $competition->institution_staff_id,
                'sort_order' => 10,
            ]);
        }
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
        $response->assertSee('class="ip-wizard-stage"', false);
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
        $response->assertSee('aria-controls="field-help-partners_entry"', false);
        $response->assertSee('aria-controls="field-help-tr_subject"', false);
        $response->assertSee('aria-controls="field-help-tr_purpose"', false);
        $response->assertSee('class="ip-field-help-panel"', false);
        $response->assertSee(__('institution.field_help.done'));
        $response->assertSee(__('institution.competitions.field_help.subject.example'));
    }

    public function test_adim_2_takvim_alanlarini_sayisal_parcalar_olarak_gosterir(): void
    {
        $staff = $this->staff();
        $competition = Competition::factory()->for($staff->institution)->for($staff)->create([
            'current_step' => 2,
            'application_starts_at' => '2026-10-01 09:05:00',
        ]);

        $response = $this->actingAs($staff, 'institution')->get(
            route('institution.competitions.step.show', [$competition, 2])
        );

        $response->assertOk();
        $response->assertDontSee('type="datetime-local"', false);
        $response->assertSee('id="application_starts_at_day"', false);
        $response->assertSee('name="application_starts_at_day"', false);
        $response->assertSee('type="number"', false);
        $response->assertSee('value="01"', false);
        $response->assertSee('id="application_ends_at_month"', false);
        $response->assertSee('id="competition_ends_at_minute"', false);
        $response->assertSee('class="ip-date-time-group is-date"', false);
        $response->assertSee('class="ip-date-time-group is-time"', false);
        $response->assertSee('max="2040"', false);
        $response->assertSee('max="23"', false);
        $response->assertSee('max="59"', false);
        $response->assertSee(__('institution.competitions.calendar_numeric_hint'));
        $this->assertSame(15, substr_count($response->getContent(), 'type="number"'));
    }

    public function test_adim_2_parcali_takvim_degerlerini_datetime_olarak_kaydeder(): void
    {
        $staff = $this->staff();
        $competition = Competition::factory()->for($staff->institution)->for($staff)->create(['current_step' => 2]);

        $response = $this->actingAs($staff, 'institution')->put(
            route('institution.competitions.step.update', [$competition, 2]),
            [
                'application_starts_at' => '2026-10-01T09:05',
                'application_ends_at' => '2026-10-31T18:10',
                'competition_ends_at' => '2026-11-05T20:15',
                'tr' => ['name' => 'Test Yarışması', 'subject' => 'Test Konu', 'purpose' => 'Test Amaç'],
                'action' => 'next',
            ]
        );

        $response->assertSessionDoesntHaveErrors();
        $response->assertRedirect(route('institution.competitions.step.show', [$competition, 3]));
        $competition->refresh();
        $this->assertSame('2026-10-01 09:05', $competition->application_starts_at?->format('Y-m-d H:i'));
        $this->assertSame('2026-10-31 18:10', $competition->application_ends_at?->format('Y-m-d H:i'));
        $this->assertSame('2026-11-05 20:15', $competition->competition_ends_at?->format('Y-m-d H:i'));
    }

    public function test_adim_2_gecersiz_takvim_bilesimi_reddedilir(): void
    {
        $staff = $this->staff();
        $competition = Competition::factory()->for($staff->institution)->for($staff)->create(['current_step' => 2]);

        $response = $this->actingAs($staff, 'institution')->put(
            route('institution.competitions.step.update', [$competition, 2]),
            [
                'application_starts_at' => '2026-02-31T09:00',
                'application_ends_at' => '2026-03-10T18:00',
                'competition_ends_at' => '2026-03-15T18:00',
                'tr' => ['name' => 'Test Yarışması', 'subject' => 'Test Konu', 'purpose' => 'Test Amaç'],
                'action' => 'next',
            ]
        );

        $response->assertSessionHasErrors('application_starts_at');
        $this->assertSame(2, $competition->fresh()->current_step);
    }

    public function test_adim_2_takvim_yil_saat_ve_dakika_sinirlarini_zorlar(): void
    {
        $staff = $this->staff();
        $competition = Competition::factory()->for($staff->institution)->for($staff)->create(['current_step' => 2]);

        foreach (['2019-10-01T09:00', '2041-10-01T09:00', '2026-10-01T24:00', '2026-10-01T23:60'] as $invalidDateTime) {
            $response = $this->actingAs($staff, 'institution')->put(
                route('institution.competitions.step.update', [$competition, 2]),
                [
                    'application_starts_at' => $invalidDateTime,
                    'application_ends_at' => '2030-10-31T18:00',
                    'competition_ends_at' => '2030-11-05T20:00',
                    'tr' => ['name' => 'Test Yarışması', 'subject' => 'Test Konu', 'purpose' => 'Test Amaç'],
                    'action' => 'next',
                ]
            );

            $response->assertSessionHasErrors('application_starts_at');
        }

        $this->assertSame(2, $competition->fresh()->current_step);
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
            [
                'infrastructure_provider' => 'external',
                'external_provider_name' => 'Kurum Portalı',
                'external_entry_url' => 'https://yarismalar.example.test',
                'external_responsibility' => '1',
                'action' => 'next',
            ]
        );

        $competition->refresh();

        $response->assertRedirect(route('institution.competitions.step.show', [$competition, 6]));
        $this->assertSame(CompetitionInfrastructureProvider::External, $competition->infrastructure_provider);
        $this->assertNull($competition->competition_type_id);
        $this->assertSame(6, $competition->current_step);

        $stepFour = $this->actingAs($staff, 'institution')->get(
            route('institution.competitions.step.show', [$competition, 4])
        );
        $stepFour->assertRedirect(route('institution.competitions.step.show', [$competition, 6]));

        $stepFive = $this->actingAs($staff, 'institution')->get(
            route('institution.competitions.step.show', [$competition, 5])
        );
        $stepFive->assertRedirect(route('institution.competitions.step.show', [$competition, 6]));
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

    public function test_adim_4_maraton_disindaki_aktif_yarisma_turu_secilince_adim_5_atlanir(): void
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

        $response->assertRedirect(route('institution.competitions.step.show', [$competition, 6]));
        $this->assertTrue($competition->competitionType->is($competitionType));
        $this->assertSame(6, $competition->current_step);
    }

    public function test_adim_gostergesi_kosullu_adimlari_gizlemek_yerine_pasif_gosterir(): void
    {
        $staff = $this->staff();
        $competitionType = CompetitionType::factory()->create(['code' => 'standard']);
        $competition = Competition::factory()->for($staff->institution)->for($staff)->create([
            'competition_type_id' => $competitionType->id,
            'infrastructure_provider' => CompetitionInfrastructureProvider::Tfsf,
            'current_step' => 6,
        ]);

        $response = $this->actingAs($staff, 'institution')->get(
            route('institution.competitions.step.show', [$competition, 6])
        );

        $response->assertOk()
            ->assertSee(__('institution.competitions.steps.5.label'))
            ->assertSee(__('institution.competitions.steps.5.inactive_hint'))
            ->assertSee('class="ip-step has-tooltip is-unavailable"', false)
            ->assertDontSee('href="'.route('institution.competitions.step.show', [$competition, 5]).'"', false);
        $this->assertSame(10, substr_count($response->getContent(), '<span class="ip-step-dot">'));
    }

    public function test_maraton_yarismasinda_adim_5_aktif_baglanti_olarak_gosterilir(): void
    {
        $staff = $this->staff();
        $competitionType = CompetitionType::factory()->create(['code' => 'photographers-marathon']);
        $competition = Competition::factory()->for($staff->institution)->for($staff)->create([
            'competition_type_id' => $competitionType->id,
            'current_step' => 6,
        ]);

        $response = $this->actingAs($staff, 'institution')->get(
            route('institution.competitions.step.show', [$competition, 6])
        );

        $response->assertOk()
            ->assertSee('href="'.route('institution.competitions.step.show', [$competition, 5]).'"', false)
            ->assertDontSee(__('institution.competitions.steps.5.inactive_hint'));
        $this->assertSame(10, substr_count($response->getContent(), '<span class="ip-step-dot">'));
    }

    public function test_adim_4_fotografcilar_maratonu_secilince_adim_5e_gecilir(): void
    {
        $staff = $this->staff();
        $competitionType = CompetitionType::factory()->create(['code' => 'photographers-marathon']);
        $competition = Competition::factory()->for($staff->institution)->for($staff)->create([
            'competition_type_id' => null,
            'current_step' => 4,
        ]);

        $response = $this->actingAs($staff, 'institution')->put(
            route('institution.competitions.step.update', [$competition, 4]),
            ['competition_type' => $competitionType->id, 'action' => 'next']
        );

        $response->assertRedirect(route('institution.competitions.step.show', [$competition, 5]));
        $this->assertSame(5, $competition->fresh()->current_step);
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

    public function test_adim_5_maraton_lokasyonunu_ve_onay_sureclerini_gosterir(): void
    {
        app()->setLocale('tr');
        $this->seed(ParticipantApprovalProcessSeeder::class);
        $staff = $this->staff();
        $type = CompetitionType::factory()->create(['code' => 'photographers-marathon']);
        $country = Country::create(['iso_alpha2' => 'TR', 'iso_alpha3' => 'TUR', 'status' => true]);
        $country->upsertTranslations(['tr' => ['official_name' => 'Türkiye']]);
        $city = $country->cities()->create(['status' => true]);
        $city->upsertTranslations(['tr' => ['official_name' => 'İstanbul']]);
        $competition = Competition::factory()->for($staff->institution)->for($staff)->create([
            'competition_type_id' => $type->id,
            'current_step' => 5,
        ]);

        $response = $this->withSession(['locale' => 'tr'])->actingAs($staff, 'institution')->get(
            route('institution.competitions.step.show', [$competition, 5])
        );

        $response->assertOk();
        $response->assertSee('Türkiye');
        $response->assertSee('Temsilci');
        $response->assertSee('Kurum');
        $response->assertSee(__('institution.competitions.location_information_title'));
        $response->assertSee(__('institution.competitions.location_information_hint'));
        $response->assertSee(__('institution.competitions.field_help.country.example'));
        $response->assertSee(__('institution.competitions.field_help.city.example'));
        $response->assertSee('aria-controls="field-help-participant_approval_process"', false);
    }

    public function test_adim_5_sehirleri_ulkeye_gore_getirir(): void
    {
        $staff = $this->staff();
        $country = Country::create(['status' => true]);
        $country->upsertTranslations(['tr' => ['official_name' => 'Türkiye']]);
        $city = $country->cities()->create(['status' => true]);
        $city->upsertTranslations(['tr' => ['official_name' => 'Ankara']]);

        $response = $this->actingAs($staff, 'institution')->getJson(
            route('institution.competitions.cities', $country)
        );

        $response->assertOk()->assertExactJson([['id' => $city->id, 'name' => 'Ankara']]);
    }

    public function test_adim_5_zorunlu_bilgiler_olmadan_ilerlenemez(): void
    {
        $staff = $this->staff();
        $type = CompetitionType::factory()->create(['code' => 'photographers-marathon']);
        $competition = Competition::factory()->for($staff->institution)->for($staff)->create([
            'competition_type_id' => $type->id,
            'current_step' => 5,
        ]);

        $response = $this->actingAs($staff, 'institution')->put(
            route('institution.competitions.step.update', [$competition, 5]),
            ['action' => 'next']
        );

        $response->assertSessionHasErrors(['regions', 'participant_approval_process']);
        $this->assertSame(5, $competition->fresh()->current_step);
    }

    public function test_adim_5_ayni_ulkeye_bagli_sehir_ve_onay_sureciyle_tamamlanir(): void
    {
        $staff = $this->staff();
        $type = CompetitionType::factory()->create(['code' => 'photographers-marathon']);
        $country = Country::create(['status' => true]);
        $country->upsertTranslations(['tr' => ['official_name' => 'Türkiye']]);
        $city = $country->cities()->create(['status' => true]);
        $city->upsertTranslations(['tr' => ['official_name' => 'İzmir']]);
        $process = ParticipantApprovalProcess::factory()->create(['code' => 'representative']);
        $competition = Competition::factory()->for($staff->institution)->for($staff)->create([
            'competition_type_id' => $type->id,
            'current_step' => 5,
        ]);

        $response = $this->actingAs($staff, 'institution')->put(
            route('institution.competitions.step.update', [$competition, 5]),
            [
                'regions' => [['country' => $country->id, 'city' => $city->id]],
                'participant_approval_process' => $process->id,
                'action' => 'next',
            ]
        );

        $response->assertRedirect(route('institution.competitions.step.show', [$competition, 6]));
        $competition->refresh();
        $this->assertSame($country->id, $competition->country_id);
        $this->assertSame($city->id, $competition->city_id);
        $this->assertSame($process->id, $competition->participant_approval_process_id);
        $this->assertSame(6, $competition->current_step);
    }

    public function test_adim_5_baska_ulkeye_bagli_sehir_secilemez(): void
    {
        $staff = $this->staff();
        $type = CompetitionType::factory()->create(['code' => 'photographers-marathon']);
        $country = Country::create(['status' => true]);
        $otherCountry = Country::create(['status' => true]);
        $city = $otherCountry->cities()->create(['status' => true]);
        $process = ParticipantApprovalProcess::factory()->create();
        $competition = Competition::factory()->for($staff->institution)->for($staff)->create([
            'competition_type_id' => $type->id,
            'current_step' => 5,
        ]);

        $response = $this->actingAs($staff, 'institution')->put(
            route('institution.competitions.step.update', [$competition, 5]),
            [
                'regions' => [['country' => $country->id, 'city' => $city->id]],
                'participant_approval_process' => $process->id,
                'action' => 'next',
            ]
        );

        $response->assertSessionHasErrors('regions.0.city');
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

        $response->assertRedirect(route('institution.competitions.step.show', [$competition, 2]));
        $this->assertSame(CompetitionStatus::Draft, $competition->fresh()->status);
    }

    public function test_yarisma_kitlesi_secilmeden_onaya_gonderilemez(): void
    {
        $staff = $this->staff();
        $competition = Competition::factory()->for($staff->institution)->for($staff)->create(['audience' => null]);

        $response = $this->actingAs($staff, 'institution')->post(route('institution.competitions.submit', $competition));

        $response->assertRedirect(route('institution.competitions.step.show', [$competition, 1]));
        $this->assertSame(CompetitionStatus::Draft, $competition->fresh()->status);
    }

    public function test_yarisma_alt_yapisi_secilmeden_onaya_gonderilemez(): void
    {
        $staff = $this->staff();
        $competition = Competition::factory()->for($staff->institution)->for($staff)->create([
            'infrastructure_provider' => null,
        ]);

        $response = $this->actingAs($staff, 'institution')->post(route('institution.competitions.submit', $competition));

        $response->assertRedirect(route('institution.competitions.step.show', [$competition, 3]));
        $this->assertSame(CompetitionStatus::Draft, $competition->fresh()->status);
    }

    public function test_tfsf_alt_yapisinda_yarisma_turu_secilmeden_onaya_gonderilemez(): void
    {
        $staff = $this->staff();
        $competition = Competition::factory()->for($staff->institution)->for($staff)->create([
            'competition_type_id' => null,
        ]);

        $response = $this->actingAs($staff, 'institution')->post(route('institution.competitions.submit', $competition));

        $response->assertRedirect(route('institution.competitions.step.show', [$competition, 4]));
        $this->assertSame(CompetitionStatus::Draft, $competition->fresh()->status);
    }

    public function test_harici_alt_yapida_tamamlanmamis_adimlar_onaya_gondermeyi_engeller(): void
    {
        $staff = $this->staff();
        $competition = Competition::factory()->for($staff->institution)->for($staff)->create([
            'infrastructure_provider' => CompetitionInfrastructureProvider::External,
            'competition_type_id' => null,
        ]);
        $this->completeCategoryStep($competition);
        $this->completeAwardStep($competition);

        $response = $this->actingAs($staff, 'institution')->post(route('institution.competitions.submit', $competition));

        $response->assertRedirect(route('institution.competitions.step.show', [$competition, 8]));
        $this->assertSame(CompetitionStatus::Draft, $competition->fresh()->status);
    }

    public function test_fotografcilar_maratonu_adim_5_tamamlanmadan_onaya_gonderilemez(): void
    {
        $staff = $this->staff();
        $type = CompetitionType::factory()->create(['code' => 'photographers-marathon']);
        $competition = Competition::factory()->for($staff->institution)->for($staff)->create([
            'competition_type_id' => $type->id,
            'country_id' => null,
            'city_id' => null,
            'participant_approval_process_id' => null,
        ]);

        $response = $this->actingAs($staff, 'institution')->post(route('institution.competitions.submit', $competition));

        $response->assertRedirect(route('institution.competitions.step.show', [$competition, 5]));
        $this->assertSame(CompetitionStatus::Draft, $competition->fresh()->status);
    }

    public function test_henuz_uygulanmayan_adimlar_onaya_gondermeyi_engeller(): void
    {
        $staff = $this->staff();
        $competition = Competition::factory()->for($staff->institution)->for($staff)->create();
        $this->completeCategoryStep($competition);
        $this->completeAwardStep($competition);
        $this->completeJurorStep($competition);

        $response = $this->actingAs($staff, 'institution')->post(route('institution.competitions.submit', $competition));

        $competition->refresh();

        $response->assertRedirect(route('institution.competitions.step.show', [$competition, 9]));
        $this->assertSame(CompetitionStatus::Draft, $competition->status);
        $this->assertNull($competition->submitted_at);
        $this->assertSame(0, $competition->statusLogs()->count());
    }

    public function test_adim_10_tum_basvuru_bilgilerini_salt_okunur_ozetler_ve_fiyatlandirma_bekler(): void
    {
        $staff = $this->staff();
        $competition = Competition::factory()->for($staff->institution)->for($staff)->create(['current_step' => 10]);
        $this->completeCategoryStep($competition);
        $this->completeAwardStep($competition);
        $this->completeJurorStep($competition);

        $response = $this->actingAs($staff, 'institution')
            ->get(route('institution.competitions.step.show', [$competition, 10]));

        $response->assertOk();
        $response->assertSee(__('institution.competitions.summary.title'));
        $response->assertSee($competition->getTranslation('tr', false)?->name);
        $response->assertSee(__('institution.competitions.summary.section_categories'));
        $response->assertSee(__('institution.competitions.summary.section_awards'));
        $response->assertSee(__('institution.competitions.summary.section_jury'));
        $response->assertSee(__('institution.competitions.summary.pricing_blocker_title'));
        $response->assertSee('disabled', false);
        $response->assertDontSee('data-wizard-form', false);
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
