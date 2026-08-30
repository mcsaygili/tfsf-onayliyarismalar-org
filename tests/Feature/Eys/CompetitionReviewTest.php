<?php

namespace Tests\Feature\Eys;

use App\Enums\CompetitionAudience;
use App\Enums\CompetitionInfrastructureProvider;
use App\Enums\CompetitionStatus;
use App\Enums\Module;
use App\Models\AgeEligibilityRule;
use App\Models\AwardReference;
use App\Models\CaptureDevice;
use App\Models\Competition;
use App\Models\CompetitionReview;
use App\Models\CompetitionType;
use App\Models\Country;
use App\Models\EysUser;
use App\Models\EvaluationCriterion;
use App\Models\MemberGroup;
use App\Models\ParticipantApprovalProcess;
use App\Models\ParticipantGender;
use App\Models\Permission;
use App\Models\ProcessingMethod;
use App\Models\RegulationItem;
use Database\Seeders\AwardReferenceSeeder;
use Database\Seeders\CompetitionCategoryReferenceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class CompetitionReviewTest extends TestCase
{
    use RefreshDatabase;

    private function reviewer(): EysUser
    {
        $user = EysUser::factory()->create();

        app(PermissionRegistrar::class)->setPermissionsTeamId(Module::Institution->value);
        Permission::firstOrCreate(['name' => 'institution.competitions.manage', 'guard_name' => 'eys']);
        $user->givePermissionTo('institution.competitions.manage');

        return $user;
    }

    private function completeCategoryStep(Competition $competition): void
    {
        $this->seed(CompetitionCategoryReferenceSeeder::class);
        $category = $competition->categories()->create(['sort_order' => 10, 'age_eligibility_rule_id' => AgeEligibilityRule::firstOrFail()->id]);
        $category->upsertTranslations(['tr' => ['name' => 'Genel']]);
        $category->genders()->sync([ParticipantGender::firstOrFail()->id]);
        $category->memberGroups()->sync([MemberGroup::firstOrFail()->id]);
        $category->captureDevices()->sync([CaptureDevice::firstOrFail()->id]);
        $category->processingMethods()->sync([ProcessingMethod::firstOrFail()->id]);

        RegulationItem::active()->where('content_type', 'institution_input')->each(function (RegulationItem $item) use ($competition): void {
            $competition->regulationInputs()->create([
                'regulation_item_id' => $item->id,
                'locale' => 'tr',
                'content' => 'Kuruma özel koşul.',
            ]);
        });
    }

    private function startReview(EysUser $reviewer, Competition $competition): CompetitionReview
    {
        $response = $this->actingAs($reviewer, 'eys')->post(route('eys.competitions.start', $competition));
        $response->assertRedirect();

        return $competition->reviews()->with('steps')->firstOrFail();
    }

    private function approveEveryReviewStep(EysUser $reviewer, Competition $competition): CompetitionReview
    {
        $review = $this->startReview($reviewer, $competition);
        $steps = $review->steps->mapWithKeys(fn ($step) => [
            $step->step_number => ['status' => 'approved', 'note' => null],
        ])->all();

        $this->actingAs($reviewer, 'eys')
            ->patch(route('eys.competitions.save-review', $competition), ['steps' => $steps])
            ->assertRedirect();

        return $review->fresh('steps');
    }

    public function test_yetkisiz_kullanici_yarisma_listesini_goremez(): void
    {
        $user = EysUser::factory()->create();

        $response = $this->actingAs($user, 'eys')->get(route('eys.competitions.index'));

        $response->assertForbidden();
    }

    public function test_inceleme_ekrani_yarisma_kitlesini_alt_yapisini_ve_turunu_okunabilir_gosterir(): void
    {
        $reviewer = $this->reviewer();
        $competition = Competition::factory()->create([
            'audience' => CompetitionAudience::International,
            'infrastructure_provider' => CompetitionInfrastructureProvider::External,
        ]);

        $response = $this->actingAs($reviewer, 'eys')->get(route('eys.competitions.show', $competition));

        $response->assertOk();
        $response->assertSee(__('eys.competitions.fields.audience'));
        $response->assertSee(__('eys.competitions.field_values.audience.international'));
        $response->assertSee(__('eys.competitions.fields.infrastructure_provider'));
        $response->assertSee(__('eys.competitions.field_values.infrastructure_provider.external'));
        $response->assertDontSee(__('eys.competitions.fields.competition_type'));

        $tfsfCompetition = Competition::factory()->create();
        $tfsfResponse = $this->actingAs($reviewer, 'eys')->get(route('eys.competitions.show', $tfsfCompetition));

        $tfsfResponse->assertOk();
        $tfsfResponse->assertSee(__('eys.competitions.fields.competition_type'));
        $tfsfResponse->assertSee($tfsfCompetition->competitionType->name);
    }

    public function test_onaylama_durumu_ve_yayin_tarihini_gunceller_ve_log_yazar(): void
    {
        $reviewer = $this->reviewer();
        $competition = Competition::factory()->submitted()->create();
        $this->approveEveryReviewStep($reviewer, $competition);

        $response = $this->actingAs($reviewer, 'eys')->post(route('eys.competitions.approve', $competition));

        $competition->refresh();

        $response->assertRedirect(route('eys.competitions.index'));
        $this->assertSame(CompetitionStatus::Approved, $competition->status);
        $this->assertNotNull($competition->published_at);
        $this->assertSame($reviewer->id, $competition->reviewed_by);
        $this->assertTrue($competition->statusLogs()->where('action', 'approved')->exists());
    }

    public function test_bekleyen_juri_daveti_eys_onayini_engeller_ama_basvuruyu_beklemede_tutar(): void
    {
        $reviewer = $this->reviewer();
        $competition = Competition::factory()->submitted()->create();
        $category = $competition->categories()->create(['sort_order' => 10]);
        $category->upsertTranslations(['tr' => ['name' => 'Genel']]);
        $invitation = $competition->juryInvitations()->create([
            'institution_id' => $competition->institution_id,
            'invited_by' => $competition->institution_staff_id,
            'email' => 'bekleyen.juri@example.test',
            'first_name' => 'Bekleyen',
            'last_name' => 'Jüri',
            'locale' => 'tr',
            'sent_at' => now(),
        ]);
        $category->jurorAssignments()->create([
            'jury_invitation_id' => $invitation->id,
            'assigned_by' => $competition->institution_staff_id,
            'sort_order' => 10,
        ]);
        $this->approveEveryReviewStep($reviewer, $competition);

        $showResponse = $this->actingAs($reviewer, 'eys')->get(route('eys.competitions.show', $competition));
        $showResponse->assertOk();
        $showResponse->assertSee(__('eys.competitions.jury_approval_blocked'));
        $showResponse->assertSee('disabled', false);

        $approveResponse = $this->actingAs($reviewer, 'eys')->post(route('eys.competitions.approve', $competition));

        $approveResponse->assertRedirect();
        $approveResponse->assertSessionHasErrors('approval');
        $this->assertSame(CompetitionStatus::WaitingRequirements, $competition->fresh()->status);
        $this->assertNull($competition->fresh()->published_at);
        $this->assertDatabaseMissing('competition_status_logs', [
            'competition_id' => $competition->id,
            'action' => 'approved',
        ]);
    }

    public function test_maraton_lokasyonu_ve_katilimci_onay_sureci_okunabilir_gosterilir(): void
    {
        app()->setLocale('tr');
        $type = CompetitionType::factory()->create(['code' => 'photographers-marathon']);
        $country = Country::create(['status' => true]);
        $country->upsertTranslations(['tr' => ['official_name' => 'Türkiye']]);
        $city = $country->cities()->create(['status' => true]);
        $city->upsertTranslations(['tr' => ['official_name' => 'İstanbul']]);
        $process = ParticipantApprovalProcess::factory()->create(['code' => 'representative']);
        $process->upsertTranslations(['tr' => ['name' => 'Temsilci', 'description' => 'Açıklama']]);
        $competition = Competition::factory()->create([
            'competition_type_id' => $type->id,
            'country_id' => $country->id,
            'city_id' => $city->id,
            'participant_approval_process_id' => $process->id,
        ]);

        $response = $this->withSession(['locale' => 'tr'])->actingAs($this->reviewer(), 'eys')
            ->get(route('eys.competitions.show', $competition));

        $response->assertOk();
        $response->assertSee('Türkiye');
        $response->assertSee('İstanbul');
        $response->assertSee('Temsilci');
        $response->assertDontSee($country->id);
        $response->assertDontSee($city->id);
    }

    public function test_kategori_odulleri_inceleme_ekraninda_kategori_bazli_gosterilir(): void
    {
        app()->setLocale('tr');
        $competition = Competition::factory()->create();
        $this->completeCategoryStep($competition);
        $this->seed(AwardReferenceSeeder::class);

        $category = $competition->categories()->firstOrFail();
        $reference = AwardReference::query()->whereHas('translations', fn ($query) => $query
            ->where('locale', 'tr')
            ->where('name', 'Altın Madalya'))->firstOrFail();
        $award = $category->awards()->create([
            'award_reference_id' => $reference->id,
            'quantity' => 2,
            'sort_order' => 10,
        ]);
        $award->upsertTranslations(['tr' => [
            'special_award_text' => 'Jüri Özel Ödülü',
            'material_award' => '10.000 TL + Plaket',
        ]]);

        $response = $this->withSession(['locale' => 'tr'])
            ->actingAs($this->reviewer(), 'eys')
            ->get(route('eys.competitions.show', $competition));

        $response->assertOk();
        $response->assertSee('Kategori Ödülleri');
        $response->assertSee('Altın Madalya');
        $response->assertSee('Jüri Özel Ödülü');
        $response->assertSee('10.000 TL + Plaket');
    }

    public function test_degerlendirme_kriterleri_inceleme_ekraninda_kategori_bazli_gosterilir(): void
    {
        app()->setLocale('tr');
        $competition = Competition::factory()->create();
        $this->completeCategoryStep($competition);
        $category = $competition->categories()->firstOrFail();
        $criterion = EvaluationCriterion::create([
            'code' => 'composition', 'default_min_score' => 0, 'default_max_score' => 10,
            'default_weight' => 1, 'sort_order' => 10, 'status' => true,
        ]);
        $criterion->upsertTranslations(['tr' => ['name' => 'Kompozisyon', 'description' => 'Kadraj ve görsel düzen.']]);
        $category->evaluationCriteria()->create([
            'evaluation_criterion_id' => $criterion->id,
            'min_score' => 1,
            'max_score' => 15,
            'weight' => 2,
            'sort_order' => 10,
        ]);

        $response = $this->withSession(['locale' => 'tr'])
            ->actingAs($this->reviewer(), 'eys')
            ->get(route('eys.competitions.show', $competition));

        $response->assertOk()
            ->assertSee('Değerlendirme Kriterleri')
            ->assertSee('Kompozisyon')
            ->assertSee('1–15')
            ->assertSee('Göreli Ağırlık');
    }

    public function test_reddetme_mesaj_zorunludur_ve_durumu_gunceller(): void
    {
        $reviewer = $this->reviewer();
        $competition = Competition::factory()->submitted()->create();

        $withoutMessage = $this->actingAs($reviewer, 'eys')->post(route('eys.competitions.reject', $competition));
        $withoutMessage->assertSessionHasErrors('message');

        $response = $this->actingAs($reviewer, 'eys')->post(route('eys.competitions.reject', $competition), [
            'message' => 'Eksik belge.',
        ]);

        $competition->refresh();

        $response->assertRedirect(route('eys.competitions.index'));
        $this->assertSame(CompetitionStatus::Rejected, $competition->status);
        $this->assertSame('Eksik belge.', $competition->latest_review_message);
        $this->assertTrue($competition->statusLogs()->where('action', 'rejected')->exists());
    }

    public function test_bilgi_talebi_durumu_needs_info_yapar_ve_log_yazar(): void
    {
        $reviewer = $this->reviewer();
        $competition = Competition::factory()->submitted()->create();
        $review = $this->startReview($reviewer, $competition);
        $steps = $review->steps->mapWithKeys(fn ($step) => [
            $step->step_number => [
                'status' => $step->step_number === 2 ? 'correction_required' : 'approved',
                'note' => $step->step_number === 2 ? 'Konu alanını detaylandırın.' : null,
            ],
        ])->all();

        $response = $this->actingAs($reviewer, 'eys')->patch(route('eys.competitions.request-info', $competition), [
            'steps' => $steps,
            'message' => 'Yarışma bilgilerini güncelleyin.',
        ]);

        $competition->refresh();

        $response->assertRedirect(route('eys.competitions.index'));
        $this->assertSame(CompetitionStatus::NeedsInfo, $competition->status);
        $this->assertSame('Yarışma bilgilerini güncelleyin.', $competition->latest_review_message);
        $this->assertSame(2, $competition->current_step);
        $this->assertTrue($competition->statusLogs()->where('action', 'correction_requested')->exists());
        $this->assertDatabaseHas('competition_review_steps', [
            'competition_review_id' => $review->id,
            'step_number' => 2,
            'status' => 'correction_required',
            'note' => 'Konu alanını detaylandırın.',
        ]);
    }

    public function test_inceleme_baslatilinca_adim_bazli_kontrol_listesi_olusur(): void
    {
        $reviewer = $this->reviewer();
        $competition = Competition::factory()->submitted()->create();

        $review = $this->startReview($reviewer, $competition);

        $this->assertSame(CompetitionStatus::UnderReview, $competition->fresh()->status);
        $this->assertSame($reviewer->id, $review->reviewer_id);
        $this->assertTrue($review->steps->contains('step_number', 2));
        $this->assertFalse($review->steps->contains('step_number', 5));
        $this->assertTrue($competition->statusLogs()->where('action', 'review_started')->exists());
    }

    public function test_tum_adimlar_uygun_bulunmadan_onay_verilemez(): void
    {
        $reviewer = $this->reviewer();
        $competition = Competition::factory()->submitted()->create();
        $this->startReview($reviewer, $competition);

        $response = $this->actingAs($reviewer, 'eys')->post(route('eys.competitions.approve', $competition));

        $response->assertSessionHasErrors('approval');
        $this->assertSame(CompetitionStatus::UnderReview, $competition->fresh()->status);
    }

    public function test_kurum_sadece_duzeltme_istenen_adimi_duzenleyebilir_ve_yanit_durumu_kaydedilir(): void
    {
        $reviewer = $this->reviewer();
        $competition = Competition::factory()->submitted()->create();
        $review = $this->startReview($reviewer, $competition);
        $steps = $review->steps->mapWithKeys(fn ($step) => [
            $step->step_number => [
                'status' => $step->step_number === 2 ? 'correction_required' : 'approved',
                'note' => $step->step_number === 2 ? 'Amaç alanını netleştirin.' : null,
            ],
        ])->all();

        $this->actingAs($reviewer, 'eys')->patch(route('eys.competitions.request-info', $competition), ['steps' => $steps]);
        $competition->refresh();
        $staff = $competition->institutionStaff;

        $this->actingAs($staff, 'institution')
            ->get(route('institution.competitions.step.show', [$competition, 1]))
            ->assertRedirect(route('institution.competitions.step.show', [$competition, 2]));

        $translation = $competition->getTranslation('tr', false);
        $this->actingAs($staff, 'institution')->put(route('institution.competitions.step.update', [$competition, 2]), [
            'partners' => $competition->partners,
            'tr' => [
                'name' => $translation?->name,
                'subject' => $translation?->subject,
                'purpose' => 'Daha açık yarışma amacı.',
            ],
            'action' => 'next',
        ])->assertRedirect(route('institution.competitions.step.show', [$competition, 11]));

        $this->assertNotNull($review->steps()->where('step_number', 2)->firstOrFail()->addressed_at);
        $this->assertTrue($competition->statusLogs()->where('action', 'correction_addressed')->exists());
    }

    public function test_kurum_needs_info_durumundayken_alan_degistirirse_field_updated_logu_yazilir_eski_yeni_degerle(): void
    {
        $reviewer = $this->reviewer();
        $competition = Competition::factory()
            ->needsInfo()
            ->withTranslations(['tr' => ['subject' => 'Eski Konu']])
            ->create();
        $staff = $competition->institutionStaff;
        $translation = $competition->getTranslation('tr', false);

        $this->actingAs($staff, 'institution')->put(
            route('institution.competitions.step.update', [$competition, 2]),
            [
                'partners' => $competition->partners,
                'tr' => [
                    'name' => $translation?->name,
                    'subject' => 'Yeni Konu',
                    'purpose' => $translation?->purpose,
                ],
                'action' => 'draft',
            ]
        );

        $log = $competition->statusLogs()->where('action', 'field_updated')->first();

        $this->assertNotNull($log);
        $this->assertSame(['Eski Konu', 'Yeni Konu'], $log->changes['tr.subject']);
    }

    public function test_needs_info_sonrasi_eksik_adimlar_yeniden_gondermeyi_engeller(): void
    {
        $competition = Competition::factory()->needsInfo()->create();
        $staff = $competition->institutionStaff;
        $this->completeCategoryStep($competition);

        $response = $this->actingAs($staff, 'institution')->post(route('institution.competitions.submit', $competition));

        $competition->refresh();

        $response->assertRedirect(route('institution.competitions.step.show', [$competition, 7]));
        $this->assertSame(CompetitionStatus::NeedsInfo, $competition->status);
        $this->assertNotSame('resubmitted', $competition->statusLogs()->first()?->action);
    }
}
