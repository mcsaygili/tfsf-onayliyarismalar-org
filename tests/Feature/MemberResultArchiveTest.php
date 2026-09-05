<?php

namespace Tests\Feature;

use App\Models\Competition;
use App\Models\CompetitionResultPublication;
use App\Models\EvaluationCriterion;
use App\Models\JuryScore;
use App\Models\User;
use App\Services\CompetitionResultService;
use App\Services\MemberResultArchiveService;
use App\Services\MemberScorecardService;
use App\Services\ResultSnapshotBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\CreatesResultSelection;
use Tests\TestCase;

class MemberResultArchiveTest extends TestCase
{
    use CreatesResultSelection, RefreshDatabase;

    private function publish(array $f, array $input = []): CompetitionResultPublication
    {
        Notification::fake();
        $this->actingAs($f['reviewer'], 'eys');
        app(CompetitionResultService::class)->aggregate($f['round']);
        $this->put(route('eys.competitions.save-result-awards', $f['competition']), ['result_context' => $this->resultContextFor($f['competition']), 'award_assignments' => [$f['award']->id => [1 => $f['result']->id]]])->assertSessionHasNoErrors();
        $this->post(route('eys.competitions.publish-results', $f['competition']), ['result_context' => $this->resultContextFor($f['competition']), ...$input])->assertSessionHasNoErrors();

        return $f['competition']->resultPublications()->firstOrFail();
    }

    private function memberUrl(array $f, CompetitionResultPublication $publication, string $photo): string
    {
        return route('competitions.result-photos.show', [$f['competition'], $publication, $photo]);
    }

    public function test_private_image_is_owned_frozen_and_never_available_through_public_aliases(): void
    {
        $f = $this->resultFixture();
        $publication = $this->publish($f);
        $asset = $publication->assets()->where('source_photo_id', $f['otherPhoto']->id)->sole();
        $owner = $f['submission']->entry->user;
        $this->assertFalse($asset->is_public);
        $this->assertSame($owner->id, $asset->owner_user_id);
        $this->assertSame(3, $publication->snapshot_version);
        $this->assertCount(2, $publication->assets);
        $url = $this->memberUrl($f, $publication, $f['otherPhoto']->id);
        $this->actingAs($owner, 'web')->get($url)->assertOk()->assertStreamedContent(Storage::disk('local')->get($asset->disk_path))->assertHeader('Cache-Control', 'no-store, private');
        $this->get(route('result.photos.show', $f['otherPhoto']->id))->assertNotFound();
        $this->get(route('result.publications.photos.show', [$publication, $f['otherPhoto']->id]))->assertNotFound();
        $otherCompetition = Competition::factory()->create();
        $this->get(route('competitions.result-photos.show', [$otherCompetition, $publication, $f['otherPhoto']->id]))->assertNotFound();
        $this->actingAs(User::factory()->create(), 'web')->get($url)->assertNotFound();
        $this->get(route('competitions.results.mine', $f['competition']))->assertOk()->assertSee(__('result_archive.missing'))->assertDontSee($f['otherPhoto']->workCode());
        Auth::guard('web')->logout();
        $this->get($url)->assertRedirect();
        $this->actingAs($f['reviewer'], 'eys')->get(route('eys.competitions.publication-photos.show', [$f['competition'], $publication, $f['otherPhoto']->id]))->assertOk();
    }

    public function test_member_scores_declarations_and_images_survive_live_edits_and_deleted_entry(): void
    {
        $f = $this->resultFixture();
        $entry = $f['submission']->entry;
        $owner = $entry->user;
        $f['photo']->update(['declaration' => ['title' => 'Frozen title', 'location' => 'Frozen location', 'taken_on' => '2020-02-03', 'story' => 'Frozen personal story']]);
        $publication = $this->publish($f);
        $before = app(MemberScorecardService::class)->forEntry($entry);
        $this->assertEquals(7, $before[$f['photo']->id][0]['average']);
        JuryScore::query()->update(['score' => 9]);
        $f['criterion']->update(['weight' => 5]);
        $f['photo']->update(['declaration' => ['title' => 'Changed title']]);
        $this->assertSame($before, app(MemberScorecardService::class)->forEntry($entry));
        $url = route('competitions.results.mine', $f['competition']);
        $response = $this->actingAs($owner, 'web')->get($url)->assertOk()->assertSee('Frozen title')->assertSee('Frozen location')->assertSee('2020-02-03')->assertSee('Frozen personal story')->assertDontSee('Changed title')->assertDontSee($f['juror']->id)->assertDontSee($f['assignment']->id);
        $this->assertCount(2, $response->viewData('archive')['photos']);
        Storage::disk('local')->delete([$f['photo']->jury_path, $f['otherPhoto']->jury_path]);
        $entry->delete();
        $this->get($url)->assertOk()->assertSee('Frozen title')->assertSee($f['otherPhoto']->workCode());
        $this->get($this->memberUrl($f, $publication, $f['otherPhoto']->id))->assertOk();
        $this->assertSame($publication->snapshot, $publication->fresh()->snapshot);
    }

    public function test_withdrawn_and_replaced_publications_never_fall_back_to_live_cards(): void
    {
        $f = $this->resultFixture();
        $publication = $this->publish($f);
        $entry = $f['submission']->entry;
        $this->post(route('eys.competitions.unpublish-results', $f['competition']), ['reason' => 'Review member result cards.'])->assertSessionHasNoErrors();
        $this->assertSame([], app(MemberScorecardService::class)->forEntry($entry));
        $this->actingAs($entry->user, 'web')->get(route('competitions.results.mine', $f['competition']))->assertOk()->assertSee(__('result_archive.unavailable'))->assertDontSee($f['photo']->workCode());
        $this->get($this->memberUrl($f, $publication, $f['otherPhoto']->id))->assertNotFound();
        $second = $this->publish($f);
        $this->assertSame(2, $second->version);
        $this->actingAs($entry->user, 'web')->get($this->memberUrl($f, $publication, $f['otherPhoto']->id))->assertNotFound();
        $this->get($this->memberUrl($f, $second, $f['otherPhoto']->id))->assertOk();
        $this->assertNotEmpty(app(MemberScorecardService::class)->forEntry($entry));
    }

    public function test_scheduled_and_suspended_results_hide_private_cards_and_images(): void
    {
        $f = $this->resultFixture();
        $publication = $this->publish($f, ['publish_at' => now()->addHour()->toDateTimeString()]);
        $entry = $f['submission']->entry;
        $url = $this->memberUrl($f, $publication, $f['otherPhoto']->id);
        $this->assertSame([], app(MemberScorecardService::class)->forEntry($entry));
        $this->actingAs($entry->user, 'web')->get($url)->assertNotFound();
        $this->travel(61)->minutes();
        $this->get($url)->assertOk();
        $this->assertNotEmpty(app(MemberScorecardService::class)->forEntry($entry));
        $f['competition']->forceFill(['publication_state' => 'suspended'])->save();
        $this->get($url)->assertNotFound();
        $this->get(route('competitions.results.mine', $f['competition']))->assertNotFound();
        $this->assertSame([], app(MemberScorecardService::class)->forEntry($entry));
    }

    public function test_legacy_publications_do_not_invent_detailed_scores_from_live_records(): void
    {
        $f = $this->resultFixture();
        $f['competition']->forceFill(['results_published_at' => now(), 'results_publication_version' => 1])->save();
        $f['competition']->resultPublications()->create(['version' => 1, 'snapshot_version' => 2, 'published_at' => now(), 'snapshot' => app(ResultSnapshotBuilder::class)->build($f['competition'], $f['round'])]);
        $this->assertSame([], app(MemberScorecardService::class)->forEntry($f['submission']->entry));
        $this->actingAs($f['submission']->entry->user, 'web')->get(route('competitions.results.mine', $f['competition']))->assertOk()->assertSee(__('result_archive.missing'))->assertDontSee(__('uye.competitions.scorecard_evaluation', ['number' => 1]));
    }

    public function test_missing_non_awarded_image_also_rolls_back_publication(): void
    {
        $f = $this->resultFixture();
        $this->put(route('eys.competitions.save-result-awards', $f['competition']), $this->awardPayload($f))->assertSessionHasNoErrors();
        Storage::disk('local')->delete($f['otherPhoto']->jury_path);
        $this->post(route('eys.competitions.publish-results', $f['competition']), ['result_context' => $this->resultContextFor($f['competition'])])->assertSessionHasErrors('results');
        $this->assertDatabaseCount('competition_result_publications', 0);
        $this->assertDatabaseCount('competition_result_assets', 0);
        $this->assertSame([], Storage::disk('local')->allFiles('result-publications'));
    }

    public function test_member_list_hides_other_owners_private_images_and_identity(): void
    {
        $f = $this->resultFixture();
        $publication = $this->publish($f);
        $peer = User::factory()->create();
        $list = app(MemberResultArchiveService::class)->resultList($f['competition'], $peer);
        $privateRow = $list['rows']->firstWhere('photo_id', $f['otherPhoto']->id);
        $this->assertNull($privateRow['image_url']);
        $this->assertArrayNotHasKey('participant', $privateRow);
        $this->assertArrayNotHasKey('participant_id', $privateRow);
        $this->actingAs($peer, 'web')->get(route('competitions.show', $f['competition']))->assertOk()->assertSee(__('result_archive.private_photo'))->assertDontSee($this->memberUrl($f, $publication, $f['otherPhoto']->id))->assertDontSee('Frozen personal story');
    }

    public function test_archived_cards_localize_labels_without_changing_values_or_exposing_jurors(): void
    {
        $f = $this->resultFixture();
        $publication = $this->publish($f);
        $raw = $publication->snapshot['member_entries'][0]['photos'][0]['scorecards'][0]['scores'][0];
        $this->assertSame(['score'], array_keys($raw));
        app()->setLocale('tr');
        $tr = app(MemberScorecardService::class)->forEntry($f['submission']->entry);
        app()->setLocale('en');
        $en = app(MemberScorecardService::class)->forEntry($f['submission']->entry);
        $this->assertNotSame($tr[$f['photo']->id][0]['scores'][0]['label'], $en[$f['photo']->id][0]['scores'][0]['label']);
        $this->assertSame($tr[$f['photo']->id][0]['scores'][0]['score'], $en[$f['photo']->id][0]['scores'][0]['score']);
    }

    public function test_archive_captures_non_finalists_and_excludes_withdrawn_or_unapproved_photos(): void
    {
        $f = $this->resultFixture();
        $final = $f['competition']->evaluationRounds()->create(['round_number' => 2, 'name' => 'Committee final']);
        $final->results()->create(['submission_photo_id' => $f['photo']->id, 'average_score' => 7, 'total_score' => 7, 'score_count' => 1, 'rank' => 1]);
        $snapshot = app(ResultSnapshotBuilder::class)->build($f['competition'], $final, true);
        $this->assertCount(1, $snapshot['results']);
        $this->assertCount(2, $snapshot['member_entries'][0]['photos']);
        $nonfinalist = collect($snapshot['member_entries'][0]['photos'])->firstWhere('photo_id', $f['otherPhoto']->id);
        $this->assertEquals(8, $nonfinalist['scorecards'][0]['average']);
        $f['otherPhoto']->update(['withdrawn_at' => now()]);
        $this->assertCount(1, app(ResultSnapshotBuilder::class)->build($f['competition'], $final, true)['member_entries'][0]['photos']);
        $f['submission']->update(['status' => 'pending_approval']);
        $this->assertSame([], app(ResultSnapshotBuilder::class)->build($f['competition'], $final, true)['member_entries']);
    }

    public function test_scorecards_ignore_rows_from_other_rounds_categories_and_unsubmitted_votes(): void
    {
        $f = $this->resultFixture();
        $before = app(MemberScorecardService::class)->captureForCompetition($f['competition']);
        $otherCategory = $f['competition']->categories()->create(['sort_order' => 20]);
        $otherCriterion = $otherCategory->evaluationCriteria()->create(['evaluation_criterion_id' => $f['criterion']->evaluation_criterion_id, 'min_score' => 3, 'max_score' => 9, 'weight' => 100]);
        $otherAssignment = $otherCategory->jurorAssignments()->create(['juror_id' => $f['juror']->id]);
        $nullAssignment = $f['submission']->category->jurorAssignments()->create(['juror_id' => null]);
        $foreignRound = Competition::factory()->create()->evaluationRounds()->create(['round_number' => 1, 'name' => 'Other competition']);
        $base = ['competition_evaluation_round_id' => $f['round']->id, 'juror_assignment_id' => $f['assignment']->id, 'submission_photo_id' => $f['photo']->id, 'criterion_assignment_id' => $f['criterion']->id, 'score' => 9, 'submitted_at' => now()];
        foreach ([['criterion_assignment_id' => $otherCriterion->id], ['juror_assignment_id' => $otherAssignment->id], ['juror_assignment_id' => $nullAssignment->id], ['competition_evaluation_round_id' => $foreignRound->id]] as $override) {
            JuryScore::create(array_replace($base, $override));
        }
        $draftCriterion = $f['submission']->category->evaluationCriteria()->create(['evaluation_criterion_id' => EvaluationCriterion::create(['code' => 'draft-score', 'status' => true])->id, 'min_score' => 3, 'max_score' => 9, 'weight' => 100]);
        JuryScore::create(array_replace($base, ['criterion_assignment_id' => $draftCriterion->id, 'submitted_at' => null]));
        $this->assertSame($before, app(MemberScorecardService::class)->captureForCompetition($f['competition']));
    }
}
