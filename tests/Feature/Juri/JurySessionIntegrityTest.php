<?php

namespace Tests\Feature\Juri;

use App\Models\CompetitionStatusLog;
use App\Models\Juri;
use App\Services\JurySessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\Concerns\CreatesJurySession;
use Tests\TestCase;

class JurySessionIntegrityTest extends TestCase
{
    use CreatesJurySession, RefreshDatabase;

    private function updateSession(array $f, array $overrides = [])
    {
        return $this->actingAs($f['reviewer'], 'eys')->put(route('eys.competitions.jury-session.update', $f['competition']), $this->sessionPayload($f, $overrides));
    }

    public function test_failed_close_rolls_back_minutes_attendance_and_version(): void
    {
        $f = $this->sessionFixture();
        $this->updateSession($f, ['attendances' => [$f['attendance']->id => 'absent']])->assertSessionHasErrors('session');
        $this->assertNull($f['session']->fresh()->minutes);
        $this->assertSame('present', $f['attendance']->fresh()->attendance_status);
        $this->assertSame($f['session']->version, $f['session']->fresh()->version);
        $this->assertSame('open', $f['finalRound']->fresh()->status->value);
    }

    public function test_old_form_and_foreign_attendance_are_rejected(): void
    {
        $f = $this->sessionFixture();
        $this->updateSession($f, ['action' => 'save', 'location' => 'Yeni salon'])->assertSessionHasNoErrors();
        $this->updateSession($f, ['action' => 'save', 'location' => 'Eski form'])->assertSessionHasErrors('session');
        $this->assertSame('Yeni salon', $f['session']->fresh()->location);
        $f['session']->refresh();
        $this->updateSession($f, ['attendances' => ['foreign-id' => 'present']])->assertSessionHasErrors('session');
        $this->assertSame('open', $f['session']->fresh()->status);
    }

    public function test_quorum_requires_explicit_declaration_current_assignment_and_active_juror(): void
    {
        $f = $this->sessionFixture();
        $f['attendance']->update(['declared_at' => null]);
        $this->assertFalse($f['session']->hasQuorum());
        $this->updateSession($f)->assertSessionHasErrors('session');
        $f['attendance']->update(['declared_at' => now(), 'conflict_declared' => true]);
        $this->assertFalse($f['session']->hasQuorum());
        $f['attendance']->update(['conflict_declared' => false]);
        $f['juror']->update(['status' => false]);
        $this->assertFalse($f['session']->hasQuorum());
        $f['juror']->update(['status' => true]);
        $f['assignment']->delete();
        $this->assertFalse($f['session']->hasQuorum());
    }

    public function test_closed_session_freezes_minutes_attendance_and_declaration(): void
    {
        $f = $this->sessionFixture();
        $this->updateSession($f)->assertSessionHasNoErrors();
        $f['session']->refresh();
        $this->updateSession($f, ['action' => 'save', 'minutes' => 'Sonradan değiştir'])->assertSessionHasErrors('session');
        $this->actingAs($f['juror'], 'juri')->post(route('juri.sessions.declaration', $f['competition']), ['session_version' => $f['session']->version, 'conflict_declared' => true, 'conflict_note' => 'Yeni çatışma bildirimi'])->assertSessionHasErrors('session');
        $this->assertSame('Kurul değerlendirmesini tamamladı.', $f['session']->fresh()->minutes);
        $this->assertFalse($f['attendance']->fresh()->conflict_declared);
        $this->assertSame('finalized', $f['finalRound']->fresh()->status->value);
        $this->actingAs($f['juror'], 'juri')->get(route('juri.assignments.show', $f['competition']))->assertOk()->assertDontSee('Beyanı kaydet');
        $this->actingAs($f['reviewer'], 'eys')->get(route('eys.competitions.show', $f['competition']))->assertOk()->assertSee('Katıldı')->assertDontSee('Planı kaydet');
    }

    public function test_audit_failure_rolls_back_closure_and_round_status(): void
    {
        $f = $this->sessionFixture();
        Event::listen('eloquent.creating: '.CompetitionStatusLog::class, fn () => throw new \RuntimeException('Audit failure'));
        try {
            app(JurySessionService::class)->update($f['competition'], $f['reviewer'], $this->sessionPayload($f));
            $this->fail('Expected rollback.');
        } catch (\RuntimeException $e) {
            $this->assertSame('Audit failure', $e->getMessage());
        }
        $this->assertSame('open', $f['session']->fresh()->status);
        $this->assertNull($f['session']->fresh()->minutes);
        $this->assertSame('open', $f['finalRound']->fresh()->status->value);
    }

    public function test_declaration_invalidates_open_forms_and_clears_inapplicable_note(): void
    {
        $f = $this->sessionFixture();
        $this->actingAs($f['juror'], 'juri')->post(route('juri.sessions.declaration', $f['competition']), ['session_version' => $f['session']->version, 'conflict_declared' => false, 'conflict_note' => 'Artık geçersiz açıklama'])->assertSessionHasNoErrors();
        $this->assertNull($f['attendance']->fresh()->conflict_note);
        $this->updateSession($f)->assertSessionHasErrors('session');
        $this->assertSame('open', $f['session']->fresh()->status);
    }

    public function test_missing_version_and_incomplete_conflict_declaration_do_not_write(): void
    {
        $f = $this->sessionFixture();
        $this->updateSession($f, ['session_version' => null])->assertSessionHasErrors('session_version');
        $this->actingAs($f['juror'], 'juri')->post(route('juri.sessions.declaration', $f['competition']), ['session_version' => $f['session']->version, 'conflict_declared' => true])->assertSessionHasErrors('conflict_note');
        $this->assertSame($f['session']->version, $f['session']->fresh()->version);
    }

    public function test_foreign_juror_cannot_declare_for_another_session(): void
    {
        $f = $this->sessionFixture();
        $other = Juri::factory()->create();
        $this->actingAs($other, 'juri')->post(route('juri.sessions.declaration', $f['competition']), ['session_version' => $f['session']->version, 'conflict_declared' => false])->assertNotFound();
        $this->assertSame($f['session']->version, $f['session']->fresh()->version);
    }

    public function test_undecided_finalist_blocks_close_without_saving_minutes(): void
    {
        $f = $this->sessionFixture();
        $f['decision']->update(['decision' => 'finalist']);
        $this->updateSession($f)->assertSessionHasErrors('session');
        $this->assertNull($f['session']->fresh()->minutes);
    }

    public function test_final_decision_rejects_stale_session_and_foreign_decision_ids(): void
    {
        $f = $this->sessionFixture();
        $url = route('eys.competitions.save-final-round', $f['competition']);
        $this->actingAs($f['reviewer'], 'eys')->put($url, ['session_version' => $f['session']->version,
            'decisions' => [$f['decision']->id => ['decision' => 'selected', 'score' => 8, 'rank' => 1], 'foreign' => ['decision' => 'not_selected']]])->assertSessionHasErrors('decisions');
        $this->assertSame(7, $f['decision']->fresh()->score);
        $this->updateSession($f, ['action' => 'save'])->assertSessionHasNoErrors();
        $this->actingAs($f['reviewer'], 'eys')->put($url, ['session_version' => $f['session']->version,
            'decisions' => [$f['decision']->id => ['decision' => 'selected', 'score' => 8, 'rank' => 1]]])->assertSessionHasErrors('session');
        $this->assertSame(7, $f['decision']->fresh()->score);
    }

    public function test_partial_decision_update_cannot_duplicate_an_omitted_rank(): void
    {
        $f = $this->sessionFixture();
        $photo = $f['photo']->replicate();
        $photo->sha256 = hash('sha256', 'second finalist');
        $photo->save();
        $f['finalRound']->committeeDecisions()->create(['submission_photo_id' => $photo->id, 'decision' => 'selected', 'score' => 6, 'rank' => 2]);
        $this->actingAs($f['reviewer'], 'eys')->put(route('eys.competitions.save-final-round', $f['competition']), [
            'session_version' => $f['session']->version,
            'decisions' => [$f['decision']->id => ['decision' => 'selected', 'score' => 8, 'rank' => 2]],
        ])->assertSessionHasErrors('decisions.'.$f['decision']->id.'.rank');
        $this->assertSame(1, $f['decision']->fresh()->rank);
        $this->assertSame($f['session']->version, $f['session']->fresh()->version);
    }

    public function test_published_results_freeze_an_open_session_and_declaration(): void
    {
        $f = $this->sessionFixture();
        $f['competition']->forceFill(['results_published_at' => now()])->save();
        $this->updateSession($f, ['action' => 'save'])->assertSessionHasErrors('session');
        $this->actingAs($f['juror'], 'juri')->post(route('juri.sessions.declaration', $f['competition']), [
            'session_version' => $f['session']->version, 'conflict_declared' => false,
        ])->assertSessionHasErrors('session');
        $this->assertSame($f['session']->version, $f['session']->fresh()->version);
    }
}
