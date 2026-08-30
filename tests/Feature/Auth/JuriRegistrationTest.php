<?php

namespace Tests\Feature\Auth;

use App\Enums\CompetitionStatus;
use App\Models\Competition;
use App\Models\CompetitionCategory;
use App\Models\Institution;
use App\Models\InstitutionStaff;
use App\Models\Juri;
use App\Models\JuryInvitation;
use App\Notifications\Juri\JuryInvitationNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class JuriRegistrationTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{JuryInvitation, CompetitionCategory, string} */
    private function invitation(bool $expired = false): array
    {
        $institution = Institution::factory()->create();
        $staff = InstitutionStaff::factory()->for($institution)->create();
        $competition = Competition::factory()->for($institution)->for($staff)->create();
        $category = $competition->categories()->create(['sort_order' => 10]);
        $token = str_repeat('a', 64);
        $invitation = JuryInvitation::create([
            'competition_id' => $competition->id,
            'institution_id' => $institution->id,
            'invited_by' => $staff->id,
            'email' => 'davetli-juri@example.test',
            'first_name' => 'Ayşe',
            'last_name' => 'Yılmaz',
            'locale' => 'tr',
        ]);
        $invitation->forceFill([
            'token_hash' => hash('sha256', $token),
            'expires_at' => $expired ? now()->subMinute() : now()->addDays(7),
            'sent_at' => now(),
        ])->save();
        $category->jurorAssignments()->create([
            'jury_invitation_id' => $invitation->id,
            'assigned_by' => $staff->id,
            'sort_order' => 10,
        ]);

        return [$invitation, $category, $token];
    }

    public function test_herkese_acik_juri_kaydi_yoktur(): void
    {
        $this->assertFalse(Route::has('juri.register'));
        $this->get('http://'.config('domains.juri').'/register')->assertNotFound();
    }

    public function test_gecerli_davet_kayit_ekranini_gosterir(): void
    {
        [$invitation, , $token] = $this->invitation();

        $this->get(route('juri.invitation.accept', $token))
            ->assertOk()
            ->assertSee('davetli-juri@example.test')
            ->assertSee(__('juri.invitation.card_title'));

        $this->assertNotNull($invitation->fresh()->opened_at);
        $this->assertDatabaseHas('jury_invitation_events', [
            'jury_invitation_id' => $invitation->id,
            'action' => 'opened',
        ]);
    }

    public function test_mevcut_dogrulanmis_hesap_sifre_istemeden_davete_baglanir(): void
    {
        [$invitation, $category, $token] = $this->invitation();
        $juror = Juri::factory()->create(['email' => $invitation->email]);

        $this->get(route('juri.invitation.accept', $token))
            ->assertOk()
            ->assertSee(__('juri.invitation.existing_card_title'))
            ->assertDontSee('name="password"', false);

        $this->post(route('juri.invitation.accept', $token))
            ->assertRedirect(route('juri.login'));

        $this->assertSame($juror->id, $category->jurorAssignments()->firstOrFail()->juror_id);
        $this->assertDatabaseHas('jury_invitation_events', [
            'jury_invitation_id' => $invitation->id,
            'action' => 'accepted',
            'actor_id' => $juror->id,
        ]);
    }

    public function test_juri_daveti_reddedebilir(): void
    {
        [$invitation, $category, $token] = $this->invitation();

        $this->post(route('juri.invitation.decline', $token))
            ->assertRedirect(route('juri.login'));

        $invitation->refresh();
        $this->assertNotNull($invitation->declined_at);
        $this->assertNull($invitation->token_hash);
        $this->assertDatabaseHas('jury_invitation_events', [
            'jury_invitation_id' => $invitation->id,
            'action' => 'declined',
        ]);
        $this->assertNull($category->jurorAssignments()->firstOrFail()->juror_id);
        $this->get(route('juri.invitation.accept', $token))->assertNotFound();
    }

    public function test_son_bekleyen_juri_kabul_edilince_eys_denetim_kaydi_olusur(): void
    {
        [$invitation, , $token] = $this->invitation();
        $invitation->competition->forceFill(['status' => CompetitionStatus::WaitingRequirements])->save();

        $this->post(route('juri.invitation.accept', $token), [
            'first_name' => 'Ayşe',
            'last_name' => 'Yılmaz',
            'password' => 'guvenli-sifre-123',
            'password_confirmation' => 'guvenli-sifre-123',
        ])->assertRedirect(route('juri.dashboard'));

        $this->assertDatabaseHas('competition_status_logs', [
            'competition_id' => $invitation->competition_id,
            'action' => 'jury_requirements_completed',
            'from_status' => CompetitionStatus::WaitingRequirements->value,
            'to_status' => CompetitionStatus::WaitingRequirements->value,
        ]);
        $this->assertSame(CompetitionStatus::WaitingRequirements, $invitation->competition->fresh()->status);
    }

    public function test_davet_kabul_edildiginde_dogrulanmis_juri_olusturulur_ve_atama_baglanir(): void
    {
        [$invitation, $category, $token] = $this->invitation();

        $response = $this->post(route('juri.invitation.accept', $token), [
            'first_name' => 'Ayşe',
            'last_name' => 'Yılmaz',
            'password' => 'guvenli-sifre-123',
            'password_confirmation' => 'guvenli-sifre-123',
        ]);

        $juror = Juri::where('email', 'davetli-juri@example.test')->firstOrFail();
        $this->assertNotNull($juror->email_verified_at);
        $this->assertSame('institution_invitation', $juror->registration_source);
        $this->assertAuthenticatedAs($juror, 'juri');
        $response->assertRedirect(route('juri.dashboard'));

        $assignment = $category->jurorAssignments()->firstOrFail();
        $this->assertSame($juror->id, $assignment->juror_id);
        $this->assertNull($assignment->jury_invitation_id);
        $invitation->refresh();
        $this->assertSame($juror->id, $invitation->accepted_juror_id);
        $this->assertNotNull($invitation->accepted_at);
        $this->assertNull($invitation->token_hash);
    }

    public function test_suresi_dolmus_davet_kabul_edilemez(): void
    {
        [, , $token] = $this->invitation(expired: true);

        $this->get(route('juri.invitation.accept', $token))->assertStatus(410);
        $this->assertDatabaseCount('jurors', 0);
    }

    public function test_davet_baglantisi_juri_subdomainine_gider(): void
    {
        [$invitation, , $token] = $this->invitation();

        $mail = (new JuryInvitationNotification($invitation->load(['competition.translations', 'institution']), $token))
            ->toMail((object) []);

        $this->assertStringStartsWith('http://'.config('domains.juri'), $mail->actionUrl);
        $this->assertStringContainsString($token, $mail->actionUrl);
    }

    public function test_dogrulanmamis_juri_panele_erisemez(): void
    {
        $juri = Juri::factory()->unverified()->create();

        $this->actingAs($juri, 'juri')->get(route('juri.dashboard'))
            ->assertRedirect(route('juri.verification.notice'));
    }

    public function test_dogrulanmis_juri_panele_erisebilir(): void
    {
        $juri = Juri::factory()->create();

        $this->actingAs($juri, 'juri')->get(route('juri.dashboard'))->assertOk();
    }
}
