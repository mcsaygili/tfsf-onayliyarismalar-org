<?php

namespace Tests\Feature\Juri;

use App\Models\EvaluationCriterion;
use App\Models\Juri;
use App\Models\JuryScore;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\Concerns\CreatesSecuritySubmission;
use Tests\TestCase;

class JuryTagTest extends TestCase
{
    use CreatesSecuritySubmission, RefreshDatabase;

    public function test_create_is_private_scoped_and_repeatable(): void
    {
        [$juror, $competition, $category, $photo] = $this->context();
        $this->actingAs($juror, 'juri');
        $url = route('juri.tags.store', [$competition, $category]);
        $first = $this->postJson($url, ['name' => '  Favoriler  ', 'color' => '#ABCDEF', 'juror_id' => Juri::factory()->create()->id])->assertOk()->json('tag');
        $second = $this->postJson($url, ['name' => 'favoriler', 'color' => '#123456'])->assertOk()->json('tag');
        $this->assertSame($first['id'], $second['id']);
        $this->assertSame('Favoriler', $first['name']);
        $this->assertSame('#abcdef', $first['color']);
        $this->assertDatabaseCount('jury_tags', 1);
        $this->assertDatabaseHas('jury_tags', ['id' => $first['id'], 'juror_id' => $juror->id, 'competition_category_id' => $category->id]);
    }

    public function test_attach_detach_and_delete_preserve_scores_and_photo(): void
    {
        [$juror, $competition, $category, $photo] = $this->context();
        $this->actingAs($juror, 'juri');
        $round = $competition->evaluationRounds()->create(['name' => 'Round', 'round_number' => 1]);
        $criterion = EvaluationCriterion::create(['code' => 'tag-preserves-score', 'status' => true]);
        $criterionAssignment = $category->evaluationCriteria()->create([
            'evaluation_criterion_id' => $criterion->id, 'min_score' => 3, 'max_score' => 9, 'weight' => 1, 'sort_order' => 0,
        ]);
        $score = JuryScore::create([
            'competition_evaluation_round_id' => $round->id, 'juror_assignment_id' => $category->jurorAssignments()->firstOrFail()->id,
            'submission_photo_id' => $photo->id, 'criterion_assignment_id' => $criterionAssignment->id, 'score' => 7, 'submitted_at' => now(),
        ]);
        $score->refresh();
        $tag = $this->postJson(route('juri.tags.store', [$competition, $category]), ['name' => 'Tekrar bak', 'color' => '#6576ff'])->json('tag');
        $url = route('juri.tags.attach', [$competition, $category, $tag['id'], $photo]);
        $this->putJson($url)->assertOk()->assertJsonPath('tag.photo_ids', [$photo->id]);
        $this->putJson($url)->assertOk();
        $this->assertDatabaseCount('jury_tag_photos', 1);
        $this->deleteJson($url)->assertOk()->assertJsonPath('tag.photo_ids', []);
        $this->deleteJson($url)->assertOk();
        $this->putJson($url)->assertOk();
        $this->deleteJson(route('juri.tags.destroy', [$competition, $category, $tag['id']]))->assertNoContent();
        $this->assertDatabaseCount('jury_tags', 0);
        $this->assertDatabaseCount('jury_tag_photos', 0);
        $this->assertModelExists($photo);
        $this->assertSame($score->getRawOriginal(), $score->fresh()->getRawOriginal());
        $this->assertDatabaseCount('jury_scores', 1);
    }

    public function test_other_assigned_juror_cannot_read_attach_detach_or_delete_private_tags(): void
    {
        [$juror, $competition, $category, $photo] = $this->context();
        $this->actingAs($juror, 'juri');
        $tag = $this->postJson(route('juri.tags.store', [$competition, $category]), ['name' => 'PRIVATE_TAG', 'color' => '#6576ff'])->json('tag');
        $other = Juri::factory()->create();
        $category->jurorAssignments()->create(['juror_id' => $other->id]);
        $this->actingAs($other, 'juri')->getJson(route('juri.tags.index', [$competition, $category]))->assertExactJson(['tags' => []]);
        $this->get(route('juri.evaluations.show', [$competition, $category]))->assertOk()->assertDontSee('PRIVATE_TAG');
        $this->get(route('juri.evaluations.show', [$competition, $category, 'tag' => $tag['id']]))->assertNotFound();
        $this->putJson(route('juri.tags.attach', [$competition, $category, $tag['id'], $photo]))->assertNotFound();
        $this->deleteJson(route('juri.tags.detach', [$competition, $category, $tag['id'], $photo]))->assertNotFound();
        $this->deleteJson(route('juri.tags.destroy', [$competition, $category, $tag['id']]))->assertNotFound();
        $this->assertDatabaseCount('jury_tags', 1);
    }

    public function test_unassigned_and_revoked_jurors_cannot_use_tag_endpoints(): void
    {
        [$juror, $competition, $category] = $this->context();
        $this->actingAs($juror, 'juri')->postJson(route('juri.tags.store', [$competition, $category]), ['name' => 'A', 'color' => '#123456'])->assertOk();
        $category->jurorAssignments()->delete();
        $this->getJson(route('juri.tags.index', [$competition, $category]))->assertNotFound();
        $this->postJson(route('juri.tags.store', [$competition, $category]), ['name' => 'B', 'color' => '#123456'])->assertNotFound();
    }

    public function test_category_competition_and_photo_ids_cannot_cross_scope(): void
    {
        [$juror, $competition, $category, $photo] = $this->context();
        [$other, $otherCompetition, $otherCategory, $otherPhoto] = $this->context();
        $otherCategory->jurorAssignments()->create(['juror_id' => $juror->id]);
        $this->actingAs($juror, 'juri');
        $tag = $this->postJson(route('juri.tags.store', [$competition, $category]), ['name' => 'A', 'color' => '#123456'])->json('tag');
        $this->getJson(route('juri.tags.index', [$competition, $otherCategory]))->assertNotFound();
        $this->putJson(route('juri.tags.attach', [$competition, $category, $tag['id'], $otherPhoto]))->assertNotFound();
        $this->deleteJson(route('juri.tags.destroy', [$otherCompetition, $otherCategory, $tag['id']]))->assertNotFound();
        $this->assertDatabaseCount('jury_tag_photos', 0);
    }

    public function test_withdrawn_or_unapproved_photos_are_not_exposed_in_tags(): void
    {
        [$juror, $competition, $category, $photo] = $this->context();
        $this->actingAs($juror, 'juri');
        $tag = $this->postJson(route('juri.tags.store', [$competition, $category]), ['name' => 'A', 'color' => '#123456'])->json('tag');
        $url = route('juri.tags.attach', [$competition, $category, $tag['id'], $photo]);
        $this->putJson($url)->assertOk();
        $photo->update(['withdrawn_at' => now()]);
        $this->getJson(route('juri.tags.index', [$competition, $category]))->assertJsonPath('tags.0.photo_ids', []);
        $this->putJson($url)->assertNotFound();
        $this->deleteJson($url)->assertOk();
        $photo->update(['withdrawn_at' => null]);
        $photo->submission->update(['status' => 'rejected']);
        $this->putJson($url)->assertNotFound();
    }

    public function test_tag_validation_and_private_photo_payload(): void
    {
        [$juror, $competition, $category, $photo] = $this->context();
        $this->actingAs($juror, 'juri');
        $url = route('juri.tags.store', [$competition, $category]);
        $this->postJson($url, ['name' => ' ', 'color' => 'red; background:url(x)'])->assertUnprocessable()->assertJsonValidationErrors(['name', 'color']);
        $this->postJson($url, ['name' => str_repeat('x', 101), 'color' => '#123456'])->assertUnprocessable();
        $tag = $this->postJson($url, ['name' => '<script>alert(1)</script>', 'color' => '#123456'])->json('tag');
        $this->putJson(route('juri.tags.attach', [$competition, $category, $tag['id'], $photo]))->assertOk()->assertDontSee('PRIVATE_FILENAME');
        $this->get(route('juri.evaluations.show', [$competition, $category]))->assertOk()->assertDontSee('<script>alert(1)</script>', false);
    }

    public function test_personal_tags_remain_editable_when_scoring_is_closed(): void
    {
        [$juror, $competition, $category] = $this->context();
        $competition->update(['evaluation_ends_at' => now()->subMinute()]);
        $this->actingAs($juror, 'juri')->postJson(route('juri.tags.store', [$competition, $category]), ['name' => 'Archive note', 'color' => '#123456'])->assertOk();
        $this->assertDatabaseCount('jury_scores', 0);
    }

    private function context(): array
    {
        $submission = $this->securitySubmission();
        $competition = $submission->entry->competition;
        $competition->update(['application_ends_at' => now()->subDays(2), 'evaluation_starts_at' => now()->subDay(), 'evaluation_ends_at' => now()->addDay()]);
        $submission->update(['status' => 'approved']);
        $juror = Juri::factory()->create();
        $submission->category->jurorAssignments()->create(['juror_id' => $juror->id]);
        $photo = $submission->photos()->create([
            'disk_path' => 'private-test/'.Str::uuid().'.jpg', 'original_filename' => 'PRIVATE_FILENAME.jpg',
            'mime_type' => 'image/jpeg', 'file_size_bytes' => 100, 'sha256' => hash('sha256', Str::uuid()),
            'metadata_snapshot' => ['Artist' => 'PRIVATE_PERSON'],
        ]);

        return [$juror, $competition, $submission->category, $photo];
    }
}
