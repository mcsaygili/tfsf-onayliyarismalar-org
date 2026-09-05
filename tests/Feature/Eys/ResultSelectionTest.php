<?php

namespace Tests\Feature\Eys;

use App\Models\CompetitionStatusLog;
use App\Models\Juri;
use App\Models\JuryScore;
use App\Services\CompetitionResultService;
use App\Services\CompetitionResultState;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Tests\Concerns\CreatesResultSelection;
use Tests\TestCase;

class ResultSelectionTest extends TestCase
{
    use CreatesResultSelection, RefreshDatabase;

    private function saveAwards(array $f, ?array $payload = null)
    {
        return $this->put(route('eys.competitions.save-result-awards', $f['competition']), $payload ?? $this->awardPayload($f));
    }

    private function contextNow(array $f): array
    {
        $f['context'] = $this->resultContextFor($f['competition']);

        return $f;
    }

    public function test_missing_and_forged_form_context_cannot_assign_awards(): void
    {
        $f = $this->resultFixture();
        $payload = $this->awardPayload($f);
        unset($payload['result_context']);
        $this->saveAwards($f, $payload)->assertSessionHasErrors('result_context');
        $payload['result_context'] = str_repeat('a', 64);
        $this->saveAwards($f, $payload)->assertSessionHasErrors('results');
        $this->assertDatabaseCount('competition_result_awards', 0);
    }

    public function test_score_change_requires_recalculation_even_from_a_new_form(): void
    {
        $f = $this->resultFixture();
        JuryScore::where('submission_photo_id', $f['photo']->id)->update(['score' => 9]);
        $this->saveAwards($f)->assertSessionHasErrors('results');
        $f = $this->contextNow($f);
        $this->saveAwards($f)->assertSessionHasErrors('results');
        $this->assertFalse(app(CompetitionResultState::class)->isFresh($f['round']->fresh()));
        app(CompetitionResultService::class)->aggregate($f['round']);
        $f = $this->contextNow($f);
        $this->saveAwards($f)->assertSessionHasNoErrors();
        $this->assertSame(1, $f['result']->fresh()->rank);
    }

    public function test_recalculation_invalidates_an_old_form_even_if_scores_are_equal(): void
    {
        $f = $this->resultFixture();
        app(CompetitionResultService::class)->aggregate($f['round']);
        $this->saveAwards($f)->assertSessionHasErrors('results');
        $this->assertDatabaseCount('competition_result_awards', 0);
    }

    public function test_old_award_form_cannot_overwrite_a_new_selection(): void
    {
        $f = $this->resultFixture();
        $this->saveAwards($f)->assertSessionHasNoErrors();
        $this->saveAwards($f, $this->awardPayload($f, $f['otherResult']->id))->assertSessionHasErrors('results');
        $this->assertDatabaseHas('competition_result_awards', ['competition_photo_result_id' => $f['result']->id]);
        $this->assertDatabaseCount('competition_result_awards', 1);
    }

    public function test_missing_foreign_or_extra_slots_cannot_clear_existing_assignments(): void
    {
        $f = $this->resultFixture();
        $this->saveAwards($f)->assertSessionHasNoErrors();
        $f = $this->contextNow($f);
        foreach ([[], [$f['award']->id => [2 => $f['result']->id]], ['foreign' => [1 => $f['result']->id]]] as $slots) {
            $this->saveAwards($f, ['result_context' => $f['context'], 'award_assignments' => $slots])->assertSessionHasErrors('award_assignments');
            $this->assertDatabaseHas('competition_result_awards', ['competition_photo_result_id' => $f['result']->id]);
        }
    }

    public function test_changed_results_require_award_review_before_publication(): void
    {
        Notification::fake();
        $f = $this->resultFixture();
        $this->saveAwards($f)->assertSessionHasNoErrors();
        JuryScore::where('submission_photo_id', $f['photo']->id)->update(['score' => 9]);
        app(CompetitionResultService::class)->aggregate($f['round']);
        $f = $this->contextNow($f);
        $url = route('eys.competitions.publish-results', $f['competition']);
        $this->post($url, ['result_context' => $f['context']])->assertSessionHasErrors('results');
        $this->assertDatabaseCount('competition_result_publications', 0);
        $this->saveAwards($f)->assertSessionHasNoErrors();
        $f = $this->contextNow($f);
        $this->post($url, ['result_context' => $f['context']])->assertSessionHasNoErrors();
        $this->assertDatabaseCount('competition_result_publications', 1);
    }

    public function test_translated_award_definition_changes_require_review(): void
    {
        $f = $this->resultFixture();
        $this->saveAwards($f)->assertSessionHasNoErrors();
        $f['award']->upsertTranslations(['tr' => ['material_award' => 'Yeni ödül koşulu']]);
        $f = $this->contextNow($f);
        $this->post(route('eys.competitions.publish-results', $f['competition']), ['result_context' => $f['context']])->assertSessionHasErrors('results');
        $this->assertDatabaseCount('competition_result_publications', 0);
    }

    public function test_rejected_submissions_are_removed_from_aggregate_results(): void
    {
        $f = $this->resultFixture();
        $f['submission']->update(['status' => 'rejected']);
        app(CompetitionResultService::class)->aggregate($f['round']);
        $this->assertDatabaseCount('competition_photo_results', 0);
    }

    public function test_existing_final_round_cannot_be_appended_to_from_a_fresh_form(): void
    {
        $f = $this->resultFixture();
        $url = route('eys.competitions.create-final-round', $f['competition']);
        $this->post($url, ['result_context' => $f['context'], 'photo_result_ids' => [$f['result']->id]])->assertSessionHasNoErrors();
        $f = $this->contextNow($f);
        $this->post($url, ['result_context' => $f['context'], 'photo_result_ids' => [$f['otherResult']->id]])->assertSessionHasErrors('results');
        $this->assertDatabaseCount('competition_committee_decisions', 1);
    }

    public function test_award_audit_failure_rolls_back_assignments_and_source_marker(): void
    {
        $f = $this->resultFixture();
        $version = $f['competition']->fresh()->results_edit_version;
        Event::listen('eloquent.creating: '.CompetitionStatusLog::class, fn () => throw new \RuntimeException('Result audit failure'));
        $this->saveAwards($f)->assertStatus(500);
        $this->assertDatabaseCount('competition_result_awards', 0);
        $this->assertNull($f['round']->fresh()->awards_context_hash);
        $this->assertSame($version, $f['competition']->fresh()->results_edit_version);
    }

    public function test_explicit_empty_slot_removes_award_and_blocks_publication(): void
    {
        $f = $this->resultFixture();
        $this->saveAwards($f)->assertSessionHasNoErrors();
        $f = $this->contextNow($f);
        $payload = $this->awardPayload($f);
        $payload['award_assignments'][$f['award']->id][1] = null;
        $this->saveAwards($f, $payload)->assertSessionHasNoErrors();
        $this->assertDatabaseCount('competition_result_awards', 0);
        $f = $this->contextNow($f);
        $this->post(route('eys.competitions.publish-results', $f['competition']), ['result_context' => $f['context']])->assertSessionHasErrors('results');
    }

    public function test_final_round_clear_preserves_previous_awards_in_audit_record(): void
    {
        $f = $this->resultFixture();
        $this->saveAwards($f)->assertSessionHasNoErrors();
        $f = $this->contextNow($f);
        $this->post(route('eys.competitions.create-final-round', $f['competition']), ['result_context' => $f['context'], 'photo_result_ids' => [$f['result']->id]])->assertSessionHasNoErrors();
        $this->assertDatabaseCount('competition_result_awards', 0);
        $event = CompetitionStatusLog::where('action', 'final_round_created')->sole();
        $this->assertSame($f['result']->id, $event->changes['cleared_awards'][0]['competition_photo_result_id']);
    }

    public function test_malformed_old_context_and_selection_still_render_validation_errors(): void
    {
        $f = $this->resultFixture();
        $this->saveAwards($f, ['result_context' => ['invalid'], 'award_assignments' => []])->assertSessionHasErrors('result_context');
        $this->get(route('eys.competitions.show', $f['competition']))->assertOk();
        $this->post(route('eys.competitions.create-final-round', $f['competition']), ['result_context' => $f['context'], 'photo_result_ids' => 'invalid'])->assertSessionHasErrors('photo_result_ids');
        $this->get(route('eys.competitions.show', $f['competition']))->assertOk();
    }

    public function test_modified_cached_rank_requires_recalculation(): void
    {
        $f = $this->resultFixture();
        $f['result']->update(['rank' => 99]);
        $f = $this->contextNow($f);
        $this->saveAwards($f)->assertSessionHasErrors('results');
        app(CompetitionResultService::class)->aggregate($f['round']);
        $this->assertSame(2, $f['result']->fresh()->rank);
        $f = $this->contextNow($f);
        $this->saveAwards($f)->assertSessionHasNoErrors();
    }

    public function test_aggregate_excludes_score_with_a_criterion_from_another_category(): void
    {
        $f = $this->resultFixture();
        $category = $f['competition']->categories()->create(['sort_order' => 20]);
        $criterion = $category->evaluationCriteria()->create(['evaluation_criterion_id' => $f['criterion']->evaluation_criterion_id, 'min_score' => 3, 'max_score' => 9, 'weight' => 1]);
        JuryScore::create(['competition_evaluation_round_id' => $f['round']->id, 'juror_assignment_id' => $f['assignment']->id,
            'submission_photo_id' => $f['photo']->id, 'criterion_assignment_id' => $criterion->id, 'score' => 9, 'submitted_at' => now()]);
        app(CompetitionResultService::class)->aggregate($f['round']);
        $this->assertSame(1, $f['result']->fresh()->score_count);
        $this->assertEquals(7, $f['result']->fresh()->total_score);
    }

    public function test_completion_of_an_unassigned_juror_cannot_replace_a_current_juror(): void
    {
        $f = $this->resultFixture();
        $f['submission']->category->jurorAssignments()->create(['juror_id' => Juri::factory()->create()->id]);
        $orphan = $f['submission']->category->jurorAssignments()->create(['juror_id' => null]);
        $f['round']->evaluationSubmissions()->create(['juror_assignment_id' => $orphan->id, 'finalized_at' => now()]);
        app(CompetitionResultService::class)->aggregate($f['round']);
        $f = $this->contextNow($f);
        $this->post(route('eys.competitions.create-final-round', $f['competition']), ['result_context' => $f['context'], 'photo_result_ids' => [$f['result']->id]])->assertSessionHasErrors('photo_result_ids');
        $this->assertSame(0, $f['competition']->evaluationRounds()->where('is_final', true)->count());
    }
}
