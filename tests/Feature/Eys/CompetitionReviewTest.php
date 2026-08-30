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
use App\Models\CompetitionType;
use App\Models\Country;
use App\Models\EysUser;
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
        $competition = Competition::factory()->pendingReview()->create();

        $response = $this->actingAs($reviewer, 'eys')->post(route('eys.competitions.approve', $competition));

        $competition->refresh();

        $response->assertRedirect(route('eys.competitions.index'));
        $this->assertSame(CompetitionStatus::Approved, $competition->status);
        $this->assertNotNull($competition->published_at);
        $this->assertSame($reviewer->id, $competition->reviewed_by);
        $this->assertSame('approved', $competition->statusLogs()->first()->action);
    }

    public function test_bekleyen_juri_daveti_eys_onayini_engeller_ama_basvuruyu_beklemede_tutar(): void
    {
        $reviewer = $this->reviewer();
        $competition = Competition::factory()->pendingReview()->create();
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

        $showResponse = $this->actingAs($reviewer, 'eys')->get(route('eys.competitions.show', $competition));
        $showResponse->assertOk();
        $showResponse->assertSee(__('eys.competitions.jury_approval_blocked'));
        $showResponse->assertSee('disabled', false);

        $approveResponse = $this->actingAs($reviewer, 'eys')->post(route('eys.competitions.approve', $competition));

        $approveResponse->assertRedirect();
        $approveResponse->assertSessionHasErrors('approval');
        $this->assertSame(CompetitionStatus::PendingReview, $competition->fresh()->status);
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

    public function test_reddetme_mesaj_zorunludur_ve_durumu_gunceller(): void
    {
        $reviewer = $this->reviewer();
        $competition = Competition::factory()->pendingReview()->create();

        $withoutMessage = $this->actingAs($reviewer, 'eys')->post(route('eys.competitions.reject', $competition));
        $withoutMessage->assertSessionHasErrors('message');

        $response = $this->actingAs($reviewer, 'eys')->post(route('eys.competitions.reject', $competition), [
            'message' => 'Eksik belge.',
        ]);

        $competition->refresh();

        $response->assertRedirect(route('eys.competitions.index'));
        $this->assertSame(CompetitionStatus::Rejected, $competition->status);
        $this->assertSame('Eksik belge.', $competition->latest_review_message);
        $this->assertSame('rejected', $competition->statusLogs()->first()->action);
    }

    public function test_bilgi_talebi_durumu_needs_info_yapar_ve_log_yazar(): void
    {
        $reviewer = $this->reviewer();
        $competition = Competition::factory()->pendingReview()->create();

        $response = $this->actingAs($reviewer, 'eys')->post(route('eys.competitions.request-info', $competition), [
            'message' => 'Konu alanını detaylandırın.',
        ]);

        $competition->refresh();

        $response->assertRedirect(route('eys.competitions.index'));
        $this->assertSame(CompetitionStatus::NeedsInfo, $competition->status);
        $this->assertSame('Konu alanını detaylandırın.', $competition->latest_review_message);
        $this->assertSame('info_requested', $competition->statusLogs()->first()->action);
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
