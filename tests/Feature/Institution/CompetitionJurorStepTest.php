<?php

namespace Tests\Feature\Institution;

use App\Models\Competition;
use App\Models\CompetitionCategory;
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

    /** @return array{InstitutionStaff, Competition, CompetitionCategory} */
    private function context(): array
    {
        $institution = Institution::factory()->create();
        $staff = InstitutionStaff::factory()->for($institution)->create();
        $competition = Competition::factory()->for($institution)->for($staff)->create(['current_step' => 8]);
        $category = $competition->categories()->create(['sort_order' => 10]);
        $category->upsertTranslations(['tr' => ['name' => 'Genel']]);

        return [$staff, $competition, $category];
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
            ->assertSee('ip-field-help-button', false);
    }

    public function test_her_kategori_icin_en_az_bir_juri_zorunludur(): void
    {
        [$staff, $competition, $category] = $this->context();

        $this->actingAs($staff, 'institution')
            ->put(route('institution.competitions.step.update', [$competition, 8]), [
                'categories' => [$category->id => ['jurors' => []]],
                'action' => 'next',
            ])
            ->assertSessionHasErrors("categories.{$category->id}.jurors");
    }

    public function test_sistemde_kayitli_juri_kategoriye_atanabilir(): void
    {
        [$staff, $competition, $category] = $this->context();
        $juror = Juri::factory()->create();

        $this->actingAs($staff, 'institution')
            ->put(route('institution.competitions.step.update', [$competition, 8]), [
                'categories' => [$category->id => ['jurors' => [[
                    'type' => 'existing',
                    'juror_id' => $juror->id,
                ]]]],
                'action' => 'next',
            ])
            ->assertRedirect(route('institution.competitions.step.show', [$competition, 10]));

        $this->assertDatabaseHas('competition_category_juror_assignments', [
            'competition_category_id' => $category->id,
            'juror_id' => $juror->id,
            'jury_invitation_id' => null,
        ]);
    }

    public function test_taslak_kayitta_davet_hazirlanir_ama_eposta_gonderilmez(): void
    {
        Notification::fake();
        [$staff, $competition, $category] = $this->context();

        $this->actingAs($staff, 'institution')
            ->put(route('institution.competitions.step.update', [$competition, 8]), [
                'categories' => [$category->id => ['jurors' => [[
                    'type' => 'invitation',
                    'first_name' => 'Ada',
                    'last_name' => 'Lovelace',
                    'email' => 'ada@example.test',
                    'locale' => 'en',
                ]]]],
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
        [$staff, $competition, $category] = $this->context();

        $this->actingAs($staff, 'institution')
            ->put(route('institution.competitions.step.update', [$competition, 8]), [
                'categories' => [$category->id => ['jurors' => [[
                    'type' => 'invitation',
                    'first_name' => 'Ada',
                    'last_name' => 'Lovelace',
                    'email' => 'ada@example.test',
                    'locale' => 'en',
                ]]]],
                'action' => 'next',
            ])
            ->assertSessionDoesntHaveErrors();

        $invitation = $competition->juryInvitations()->firstOrFail();
        $this->assertSame('ada@example.test', $invitation->email);
        $this->assertNotNull($invitation->token_hash);
        $this->assertNotNull($invitation->sent_at);
        $this->assertTrue($invitation->expires_at->isFuture());
        Notification::assertSentOnDemand(JuryInvitationNotification::class);
    }

    public function test_ayni_eposta_birden_fazla_kategoride_tek_davetle_kullanilir(): void
    {
        Notification::fake();
        [$staff, $competition, $category] = $this->context();
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
                    $category->id => ['jurors' => [$row]],
                    $secondCategory->id => ['jurors' => [$row]],
                ],
                'action' => 'next',
            ])
            ->assertSessionDoesntHaveErrors();

        $this->assertSame(1, $competition->juryInvitations()->count());
        $this->assertDatabaseCount('competition_category_juror_assignments', 2);
        Notification::assertSentOnDemandTimes(JuryInvitationNotification::class, 1);
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
