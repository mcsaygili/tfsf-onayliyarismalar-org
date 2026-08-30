<?php

namespace Tests\Feature\Institution;

use App\Models\Competition;
use App\Models\CompetitionCategory;
use App\Models\EvaluationCriterion;
use App\Models\Institution;
use App\Models\InstitutionStaff;
use App\Models\Juri;
use App\Notifications\Juri\JuryInvitationNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class CompetitionJurorStepTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{InstitutionStaff, Competition, CompetitionCategory, EvaluationCriterion} */
    private function context(): array
    {
        $institution = Institution::factory()->create();
        $staff = InstitutionStaff::factory()->for($institution)->create();
        $competition = Competition::factory()->for($institution)->for($staff)->create(['current_step' => 8]);
        $category = $competition->categories()->create(['sort_order' => 10]);
        $category->upsertTranslations(['tr' => ['name' => 'Genel']]);
        $criterion = EvaluationCriterion::create([
            'code' => 'general-evaluation',
            'default_min_score' => 3,
            'default_max_score' => 9,
            'default_weight' => 1,
            'sort_order' => 10,
            'status' => true,
        ]);
        $criterion->upsertTranslations([
            'tr' => ['name' => 'Genel Değerlendirme', 'description' => 'Eserin bütünsel değerlendirmesi.'],
            'en' => ['name' => 'General Evaluation', 'description' => 'Overall evaluation of the work.'],
        ]);

        return [$staff, $competition, $category, $criterion];
    }

    /** @return array<int, array<string, int|string>> */
    private function criterionRows(EvaluationCriterion $criterion): array
    {
        return [[
            'evaluation_criterion_id' => $criterion->id,
            'min_score' => 3,
            'max_score' => 9,
            'weight' => 1,
        ]];
    }

    public function test_adim_8_kategori_bazli_juri_arama_ve_davet_arayuzunu_gosterir(): void
    {
        [$staff, $competition] = $this->context();

        $this->actingAs($staff, 'institution')
            ->get(route('institution.competitions.step.show', [$competition, 8]))
            ->assertOk()
            ->assertSee(__('institution.competitions.jury_configuration_title'))
            ->assertSee('Genel')
            ->assertSee(__('institution.competitions.registered_juror_title'))
            ->assertSee(__('institution.competitions.invite_juror_title'))
            ->assertSee(__('institution.competitions.criteria_configuration_title'))
            ->assertSee('General Evaluation')
            ->assertSee(__('institution.competitions.default_criterion_note'))
            ->assertSee('readonly', false)
            ->assertSee('ip-field-help-button', false);
    }

    public function test_her_kategori_icin_en_az_bir_juri_zorunludur(): void
    {
        [$staff, $competition, $category, $criterion] = $this->context();

        $this->actingAs($staff, 'institution')
            ->put(route('institution.competitions.step.update', [$competition, 8]), [
                'categories' => [$category->id => ['jurors' => []]],
                'action' => 'next',
            ])
            ->assertSessionHasErrors("categories.{$category->id}.jurors");
    }

    public function test_sistemde_kayitli_juri_kategoriye_atanabilir(): void
    {
        [$staff, $competition, $category, $criterion] = $this->context();
        $juror = Juri::factory()->create();

        $this->actingAs($staff, 'institution')
            ->put(route('institution.competitions.step.update', [$competition, 8]), [
                'categories' => [$category->id => [
                    'jurors' => [[
                        'type' => 'existing',
                        'juror_id' => $juror->id,
                    ]],
                    'criteria' => $this->criterionRows($criterion),
                ]],
                'action' => 'next',
            ])
            ->assertRedirect(route('institution.competitions.step.show', [$competition, 9]));

        $this->assertDatabaseHas('competition_category_juror_assignments', [
            'competition_category_id' => $category->id,
            'juror_id' => $juror->id,
            'jury_invitation_id' => null,
        ]);
        $this->assertDatabaseHas('competition_category_evaluation_criteria', [
            'competition_category_id' => $category->id,
            'evaluation_criterion_id' => $criterion->id,
            'min_score' => 3,
            'max_score' => 9,
            'weight' => 1,
        ]);
    }

    public function test_taslak_kayitta_davet_hazirlanir_ama_eposta_gonderilmez(): void
    {
        Notification::fake();
        [$staff, $competition, $category, $criterion] = $this->context();

        $this->actingAs($staff, 'institution')
            ->put(route('institution.competitions.step.update', [$competition, 8]), [
                'categories' => [$category->id => [
                    'jurors' => [[
                        'type' => 'invitation',
                        'first_name' => 'Ada',
                        'last_name' => 'Lovelace',
                        'email' => 'ada@example.test',
                        'locale' => 'en',
                    ]],
                    'criteria' => $this->criterionRows($criterion),
                ]],
                'action' => 'draft',
            ])
            ->assertSessionDoesntHaveErrors()
            ->assertRedirect(route('institution.competitions.step.show', [$competition, 8]));

        $this->assertDatabaseHas('jury_invitations', [
            'competition_id' => $competition->id,
            'email' => 'ada@example.test',
            'sent_at' => null,
        ]);
        Notification::assertNothingSent();
    }

    public function test_ileri_secildiginde_yeni_juriye_guvenli_davet_gonderilir(): void
    {
        Notification::fake();
        [$staff, $competition, $category, $criterion] = $this->context();

        $this->actingAs($staff, 'institution')
            ->put(route('institution.competitions.step.update', [$competition, 8]), [
                'categories' => [$category->id => [
                    'jurors' => [[
                        'type' => 'invitation',
                        'first_name' => 'Ada',
                        'last_name' => 'Lovelace',
                        'email' => 'ada@example.test',
                        'locale' => 'en',
                    ]],
                    'criteria' => $this->criterionRows($criterion),
                ]],
                'action' => 'next',
            ])
            ->assertSessionDoesntHaveErrors();

        $invitation = $competition->juryInvitations()->firstOrFail();
        $this->assertSame('ada@example.test', $invitation->email);
        $this->assertNotNull($invitation->token_hash);
        $this->assertNotNull($invitation->sent_at);
        $this->assertTrue($invitation->expires_at->isFuture());
        $this->assertSame(1, $invitation->send_count);
        $this->assertDatabaseHas('jury_invitation_events', [
            'jury_invitation_id' => $invitation->id,
            'action' => 'sent',
            'actor_id' => $staff->id,
        ]);
        Notification::assertSentOnDemand(JuryInvitationNotification::class);
    }

    public function test_kurum_daveti_yeniden_gonderebilir_ve_iptal_edebilir(): void
    {
        Notification::fake();
        [$staff, $competition, $category, $criterion] = $this->context();
        $payload = [
            'categories' => [$category->id => [
                'jurors' => [[
                    'type' => 'invitation',
                    'first_name' => 'Ada',
                    'last_name' => 'Lovelace',
                    'email' => 'ada@example.test',
                    'locale' => 'en',
                ]],
                'criteria' => $this->criterionRows($criterion),
            ]],
            'action' => 'next',
        ];

        $this->actingAs($staff, 'institution')
            ->put(route('institution.competitions.step.update', [$competition, 8]), $payload)
            ->assertSessionDoesntHaveErrors();

        $invitation = $competition->juryInvitations()->firstOrFail();
        $oldHash = $invitation->token_hash;
        $this->post(route('institution.competitions.jury-invitations.resend', [$competition, $invitation]))
            ->assertRedirect();

        $invitation->refresh();
        $this->assertSame(2, $invitation->send_count);
        $this->assertNotSame($oldHash, $invitation->token_hash);
        $this->assertDatabaseHas('jury_invitation_events', [
            'jury_invitation_id' => $invitation->id,
            'action' => 'resent',
        ]);

        $this->delete(route('institution.competitions.jury-invitations.cancel', [$competition, $invitation]))
            ->assertRedirect();
        $this->assertNotNull($invitation->fresh()->revoked_at);
        $this->assertDatabaseMissing('competition_category_juror_assignments', [
            'competition_category_id' => $category->id,
            'jury_invitation_id' => $invitation->id,
        ]);
        $this->assertDatabaseHas('jury_invitation_events', [
            'jury_invitation_id' => $invitation->id,
            'action' => 'cancelled',
        ]);
    }

    public function test_ayni_eposta_birden_fazla_kategoride_tek_davetle_kullanilir(): void
    {
        Notification::fake();
        [$staff, $competition, $category, $criterion] = $this->context();
        $secondCategory = $competition->categories()->create(['sort_order' => 20]);
        $secondCategory->upsertTranslations(['tr' => ['name' => 'Doğa']]);
        $row = [
            'type' => 'invitation',
            'first_name' => 'Ara',
            'last_name' => 'Güler',
            'email' => 'ara@example.test',
            'locale' => 'tr',
        ];

        $this->actingAs($staff, 'institution')
            ->put(route('institution.competitions.step.update', [$competition, 8]), [
                'categories' => [
                    $category->id => ['jurors' => [$row], 'criteria' => $this->criterionRows($criterion)],
                    $secondCategory->id => ['jurors' => [$row], 'criteria' => $this->criterionRows($criterion)],
                ],
                'action' => 'next',
            ])
            ->assertSessionDoesntHaveErrors();

        $this->assertSame(1, $competition->juryInvitations()->count());
        $this->assertDatabaseCount('competition_category_juror_assignments', 2);
        Notification::assertSentOnDemandTimes(JuryInvitationNotification::class, 1);
    }

    public function test_ileri_icin_her_kategoride_en_az_bir_degerlendirme_kriteri_zorunludur(): void
    {
        [$staff, $competition, $category] = $this->context();
        $juror = Juri::factory()->create();

        $this->actingAs($staff, 'institution')
            ->put(route('institution.competitions.step.update', [$competition, 8]), [
                'categories' => [$category->id => ['jurors' => [[
                    'type' => 'existing',
                    'juror_id' => $juror->id,
                ]], 'criteria' => []]],
                'action' => 'next',
            ])
            ->assertSessionHasErrors("categories.{$category->id}.criteria");
    }

    public function test_kriter_puan_araligi_gecersizse_kaydedilmez(): void
    {
        [$staff, $competition, $category, $criterion] = $this->context();
        $juror = Juri::factory()->create();

        $this->actingAs($staff, 'institution')
            ->put(route('institution.competitions.step.update', [$competition, 8]), [
                'categories' => [$category->id => [
                    'jurors' => [['type' => 'existing', 'juror_id' => $juror->id]],
                    'criteria' => [[
                        'evaluation_criterion_id' => $criterion->id,
                        'min_score' => 10,
                        'max_score' => 10,
                        'weight' => 1,
                    ]],
                ]],
                'action' => 'next',
            ])
            ->assertSessionHasErrors("categories.{$category->id}.criteria.0");

        $this->assertDatabaseCount('competition_category_evaluation_criteria', 0);
    }

    public function test_genel_degerlendirme_puan_araligi_3_9_disina_cikarilamaz(): void
    {
        [$staff, $competition, $category, $criterion] = $this->context();
        $juror = Juri::factory()->create();

        $this->actingAs($staff, 'institution')
            ->put(route('institution.competitions.step.update', [$competition, 8]), [
                'categories' => [$category->id => [
                    'jurors' => [['type' => 'existing', 'juror_id' => $juror->id]],
                    'criteria' => [[
                        'evaluation_criterion_id' => $criterion->id,
                        'min_score' => 0,
                        'max_score' => 10,
                        'weight' => 1,
                    ]],
                ]],
                'action' => 'next',
            ])
            ->assertSessionHasErrors("categories.{$category->id}.criteria.0");

        $this->assertDatabaseCount('competition_category_evaluation_criteria', 0);
    }

    public function test_juri_arama_sadece_aktif_ve_dogrulanmis_hesaplari_dondurur(): void
    {
        [$staff, $competition] = $this->context();
        $active = Juri::factory()->create(['first_name' => 'Aranan', 'last_name' => 'Jüri']);
        Juri::factory()->unverified()->create(['first_name' => 'Aranan', 'last_name' => 'Doğrulanmamış']);
        Juri::factory()->create(['first_name' => 'Aranan', 'last_name' => 'Pasif', 'status' => false]);

        $response = $this->actingAs($staff, 'institution')
            ->getJson(route('institution.competitions.jurors.search', [$competition, 'q' => 'Aranan']));

        $response->assertOk()->assertJsonCount(1)->assertJsonFragment(['id' => $active->id]);
    }
}
