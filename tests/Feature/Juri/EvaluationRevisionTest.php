<?php

namespace Tests\Feature\Juri;

use App\Http\Middleware\SerializeCompetitionMutation;
use App\Models\Competition;
use App\Models\CompetitionStatusLog;
use App\Models\EvaluationCriterion;
use App\Services\CompetitionSubmissionDetailsService;
use App\Services\JuryEvaluationService;
use App\Services\SubmissionApprovalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use Tests\Concerns\CreatesEvaluationRevision;
use Tests\TestCase;

class EvaluationRevisionTest extends TestCase
{
    use CreatesEvaluationRevision, RefreshDatabase;

    private function send(array $f, array $scores, ?string $context = null, bool $finalize = false)
    {
        return $this->actingAs($f['juror'], 'juri')->put(route($finalize ? 'juri.evaluations.finalize' : 'juri.evaluations.save', [$f['competition'], $f['submission']->category]),
            ['scores' => $scores, 'evaluation_context' => $context ?? $f['data']['evaluationContext']]);
    }

    public function test_missing_or_forged_context_cannot_write_scores(): void
    {
        $f = $this->evaluationFixture();
        $this->actingAs($f['juror'], 'juri')->put(route('juri.evaluations.save', [$f['competition'], $f['submission']->category]), ['scores' => []])->assertSessionHasErrors('evaluation_context');
        $this->send($f, [$f['photo']->id => [$f['criterion']->id => 7]], str_repeat('a', 64))->assertSessionHasErrors('scores');
        $this->assertDatabaseCount('jury_scores', 0);
    }

    public function test_old_score_form_cannot_overwrite_a_newer_draft(): void
    {
        $f = $this->evaluationFixture();
        $this->send($f, [$f['photo']->id => [$f['criterion']->id => 7]])->assertSessionHasNoErrors();
        $this->send($f, [$f['photo']->id => [$f['criterion']->id => 8]])->assertSessionHasErrors('scores');
        $this->assertDatabaseHas('jury_scores', ['score' => 7]);
        $this->assertSame(1, (int) $f['assignment']->fresh()->evaluation_version);
    }

    public function test_story_revision_invalidates_an_already_open_jury_form(): void
    {
        Notification::fake();
        $f = $this->evaluationFixture();
        app(CompetitionSubmissionDetailsService::class)->update($f['submission'], $f['submission']->entry->user, 0, ['category_story' => 'Yeni anlatı',
            'photos' => [['id' => $f['photo']->id, ...$f['photo']->declarationData(), 'title' => 'Yeni ad']]]);
        $this->send($f, [$f['photo']->id => [$f['criterion']->id => 7]], finalize: true)->assertSessionHasErrors('scores');
        $this->assertDatabaseCount('jury_scores', 0);
        $this->assertDatabaseCount('jury_evaluation_submissions', 0);
    }

    public function test_incomplete_finalize_rolls_back_the_scores_posted_with_it(): void
    {
        $f = $this->evaluationFixture();
        $criterion = EvaluationCriterion::create(['code' => 'second', 'status' => true]);
        $f['submission']->category->evaluationCriteria()->create(['evaluation_criterion_id' => $criterion->id, 'min_score' => 3, 'max_score' => 9, 'weight' => 1]);
        $f['data'] = app(JuryEvaluationService::class)->evaluationData($f['assignment'], $f['round']);
        $this->send($f, [$f['photo']->id => [$f['criterion']->id => 7]], finalize: true)->assertSessionHasErrors('scores');
        $this->assertDatabaseCount('jury_scores', 0);
        $this->assertSame(0, (int) $f['assignment']->fresh()->evaluation_version);
    }

    public function test_finalize_event_failure_rolls_back_scores_completion_and_version(): void
    {
        $f = $this->evaluationFixture();
        Event::listen('eloquent.creating: '.CompetitionStatusLog::class, fn () => throw new \RuntimeException('Injected audit failure'));
        try {
            app(JuryEvaluationService::class)->save($f['assignment'], $f['round'], [$f['photo']->id => [$f['criterion']->id => 7]], $f['data']['evaluationContext'], true);
            $this->fail('Audit failure must abort finalize.');
        } catch (\RuntimeException $e) {
            $this->assertSame('Injected audit failure', $e->getMessage());
        }
        $this->assertDatabaseCount('jury_scores', 0);
        $this->assertDatabaseCount('jury_evaluation_submissions', 0);
        $this->assertSame(0, (int) $f['assignment']->fresh()->evaluation_version);
    }

    public function test_closed_round_is_reread_instead_of_using_a_stale_model(): void
    {
        $f = $this->evaluationFixture();
        $f['round']->fresh()->update(['status' => 'closed']);
        $this->expectException(ValidationException::class);
        app(JuryEvaluationService::class)->save($f['assignment'], $f['round'], [], $f['data']['evaluationContext']);
    }

    public function test_fractional_scores_and_foreign_photos_are_rejected_without_partial_writes(): void
    {
        $f = $this->evaluationFixture();
        foreach ([[$f['photo']->id => [$f['criterion']->id => '7.5']], ['foreign-photo' => [$f['criterion']->id => 7]]] as $scores) {
            try {
                app(JuryEvaluationService::class)->save($f['assignment'], $f['round'], $scores, $f['data']['evaluationContext']);
                $this->fail('Invalid score must not be silently skipped or truncated.');
            } catch (ValidationException) {
                $this->assertDatabaseCount('jury_scores', 0);
            }
        }
    }

    public function test_reading_an_expired_evaluation_does_not_mutate_the_round(): void
    {
        $f = $this->evaluationFixture();
        $f['competition']->update(['evaluation_ends_at' => now()->subMinute()]);
        $this->actingAs($f['juror'], 'juri')->get(route('juri.evaluations.show', [$f['competition'], $f['submission']->category]))->assertOk();
        $this->assertSame('open', $f['round']->fresh()->status->value);
    }

    public function test_mutation_middleware_rolls_back_rendered_exception_and_error_redirect(): void
    {
        $f = $this->evaluationFixture();
        Route::middleware(['web', SerializeCompetitionMutation::class])->post('/revision-rollback/{competition}/{mode}', function (Competition $competition, string $mode) {
            $competition->update(['results_publication_version' => 999]);
            if ($mode === 'exception') {
                throw ValidationException::withMessages(['test' => 'Injected validation']);
            }

            return back()->withErrors(['test' => 'Explicit failure']);
        });
        foreach (['exception', 'redirect'] as $mode) {
            $this->post('/revision-rollback/'.$f['competition']->id.'/'.$mode)->assertSessionHasErrors('test');
            $this->assertNotSame(999, $f['competition']->fresh()->results_publication_version);
        }
    }

    public function test_rendered_criteria_and_context_use_the_same_fresh_snapshot(): void
    {
        $f = $this->evaluationFixture();
        $f['assignment']->load('category.evaluationCriteria');
        $f['criterion']->update(['max_score' => 8]);
        $data = app(JuryEvaluationService::class)->evaluationData($f['assignment'], $f['round']);
        $this->assertNotSame($f['data']['evaluationContext'], $data['evaluationContext']);
        $this->assertSame(8, (int) $data['assignment']->category->evaluationCriteria->sole()->max_score);
        $this->send($f, [$f['photo']->id => [$f['criterion']->id => 7]])->assertSessionHasErrors('scores');
        $this->assertDatabaseCount('jury_scores', 0);
    }

    public function test_participation_decision_reopens_completed_jury_and_invalidates_old_forms(): void
    {
        Notification::fake();
        $f = $this->evaluationFixture();
        $this->send($f, [$f['photo']->id => [$f['criterion']->id => 7]], finalize: true)->assertSessionHasNoErrors();
        $approval = $f['submission']->approvals()->create(['approval_type' => 'institution', 'status' => 'pending', 'sequence' => 1]);
        app(SubmissionApprovalService::class)->decide($approval, $f['competition']->institutionStaff, false, 'Uygun değil');
        $this->assertDatabaseCount('jury_evaluation_submissions', 0);
        $this->assertDatabaseHas('jury_scores', ['score' => 7, 'submitted_at' => null]);
        $this->assertSame(2, $f['assignment']->fresh()->evaluation_version);
        $data = app(JuryEvaluationService::class)->evaluationData($f['assignment'], $f['round']);
        $this->assertCount(0, $data['photos']);
        $this->assertNotSame($f['data']['evaluationContext'], $data['evaluationContext']);
    }

    public function test_final_round_blocks_old_individual_forms_and_pending_participation_decisions(): void
    {
        $f = $this->evaluationFixture();
        $f['competition']->evaluationRounds()->create(['round_number' => 2, 'name' => 'Final', 'method' => 'committee', 'status' => 'open', 'is_final' => true]);
        $this->send($f, [$f['photo']->id => [$f['criterion']->id => 7]], finalize: true)->assertSessionHasErrors('scores');
        $data = app(JuryEvaluationService::class)->evaluationData($f['assignment'], $f['round']);
        $this->assertTrue($data['evaluationLocked']);
        $approval = $f['submission']->approvals()->create(['approval_type' => 'institution', 'status' => 'pending', 'sequence' => 1]);
        try {
            app(SubmissionApprovalService::class)->decide($approval, $f['competition']->institutionStaff, false, 'Test');
            $this->fail('Final round must freeze participation decisions.');
        } catch (ValidationException) {
            $this->assertSame('pending', $approval->fresh()->status->value);
            $this->assertSame('approved', $f['submission']->fresh()->status->value);
            $this->assertDatabaseCount('jury_scores', 0);
        }
    }
}
