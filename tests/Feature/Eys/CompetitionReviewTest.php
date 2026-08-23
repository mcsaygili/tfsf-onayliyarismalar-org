<?php

namespace Tests\Feature\Eys;

use App\Enums\CompetitionAudience;
use App\Enums\CompetitionInfrastructureProvider;
use App\Enums\CompetitionStatus;
use App\Enums\Module;
use App\Models\Competition;
use App\Models\EysUser;
use App\Models\Permission;
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

    public function test_needs_info_sonrasi_yeniden_gonderilince_resubmitted_logu_yazilir_ve_pending_reviewe_doner(): void
    {
        $competition = Competition::factory()->needsInfo()->create();
        $staff = $competition->institutionStaff;

        $response = $this->actingAs($staff, 'institution')->post(route('institution.competitions.submit', $competition));

        $competition->refresh();

        $response->assertRedirect(route('institution.competitions.index'));
        $this->assertSame(CompetitionStatus::PendingReview, $competition->status);
        $this->assertSame('resubmitted', $competition->statusLogs()->first()->action);
    }
}
