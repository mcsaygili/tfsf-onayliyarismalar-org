<?php

namespace Tests\Feature;

use App\Models\CompetitionEntry;
use App\Models\Photo;
use App\Models\User;
use App\Services\CompetitionResultService;
use App\Services\CompetitionSubmissionDetailsService;
use App\Services\CompetitionSubmissionPhotoService;
use App\Services\JuryEvaluationService;
use App\Services\MemberResultArchiveService;
use App\Support\CompetitionRegulations\CompetitionRegulationContextBuilder;
use App\Support\CompetitionWizard\Step6;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\Concerns\CreatesResultSelection;
use Tests\TestCase;

class SubmissionSeriesTest extends TestCase
{
    use CreatesResultSelection, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        Storage::fake('local');
        Storage::fake('public');
    }

    private function seriesFixture(): array
    {
        $f = $this->evaluationFixture();
        $f['submission']->category->update(['photos_grouped' => true, 'photo_order_required' => false]);
        $second = $f['photo']->replicate();
        $second->sha256 = hash('sha256', 'series-second');
        $second->sort_order = 20;
        $second->save();

        return $f + compact('second');
    }

    private function details(array $f, array $positions = [2, 1]): array
    {
        return ['category_story' => 'Shared series narrative', 'photos' => collect([$f['photo'], $f['second']])->map(fn ($p, $i) => ['id' => $p->id, ...$p->declarationData(), 'position' => $positions[$i]])->all()];
    }

    public function test_grouped_series_has_required_order_even_when_separate_order_flag_is_off(): void
    {
        $f = $this->seriesFixture();
        $code = $f['submission']->seriesCode();
        app(CompetitionSubmissionDetailsService::class)->update($f['submission'], $f['submission']->entry->user, $f['submission']->fresh()->details_version, $this->details($f));
        $this->assertSame([$f['second']->id, $f['photo']->id], $f['submission']->activePhotos()->pluck('id')->all());
        $this->assertSame($code, $f['submission']->fresh()->seriesCode());
        $this->assertSame('Shared series narrative', $f['submission']->fresh()->category_story);
        $this->assertSame(1, $f['submission']->fresh()->details_version);
    }

    public function test_invalid_series_order_cannot_partially_update_story_or_photos(): void
    {
        $f = $this->seriesFixture();
        try {
            app(CompetitionSubmissionDetailsService::class)->update($f['submission'], $f['submission']->entry->user, $f['submission']->fresh()->details_version, $this->details($f, [1, 1]));
            $this->fail('Expected invalid order');
        } catch (ValidationException $exception) {
            $this->assertStringContainsString('photos.', implode(' ', array_keys($exception->errors())));
            $this->assertNull($f['submission']->fresh()->category_story);
            $this->assertSame(10, $f['photo']->fresh()->sort_order);
            $this->assertSame(0, $f['submission']->fresh()->details_version);
        }
    }

    public function test_series_edit_rejects_a_foreign_owner(): void
    {
        $f = $this->seriesFixture();
        $this->actingAs(User::factory()->create())->put(route('competitions.submission.details.update', $f['submission']), ['details_version' => 0, ...$this->details($f)])->assertNotFound();
        $this->assertSame(0, $f['submission']->fresh()->details_version);
    }

    public function test_jury_receives_whole_approved_series_in_sequence_without_owner_identity(): void
    {
        $f = $this->seriesFixture();
        $f['photo']->update(['sort_order' => 30]);
        $f['submission']->update(['category_story' => '<script>series story</script>']);
        $response = $this->actingAs($f['juror'], 'juri')->get(route('juri.evaluations.show', [$f['competition'], $f['submission']->category]));
        $response->assertOk()->assertSee($f['submission']->seriesCode())->assertSee('<script>series story</script>')->assertDontSee('<script>series story</script>', false);
        $this->assertSame([[$f['second']->id, $f['photo']->id]], $response->viewData('photoGroups'));
        $response->assertDontSee($f['submission']->entry->user_id)->assertDontSee($f['submission']->entry->user->email)->assertDontSee($f['photo']->original_filename);
        $data = $response->viewData('photos');
        $this->assertSame([$f['second']->id, $f['photo']->id], $data->pluck('id')->all());
    }

    public function test_other_members_have_separate_series_and_unapproved_series_stays_hidden(): void
    {
        $f = $this->seriesFixture();
        $entry = CompetitionEntry::create(['competition_id' => $f['competition']->id, 'user_id' => User::factory()->create()->id, 'status' => 'draft']);
        $other = $entry->submissions()->create(['competition_category_id' => $f['submission']->competition_category_id, 'status' => 'draft']);
        $copy = $f['photo']->replicate();
        $copy->competition_submission_id = $other->id;
        $copy->save();
        $this->assertNotSame($other->seriesCode(), $f['submission']->seriesCode());
        $service = app(JuryEvaluationService::class);
        $this->assertCount(1, $service->evaluationData($f['assignment'], $f['round'])['photoGroups']);
        $other->update(['status' => 'approved']);
        $groups = $service->evaluationData($f['assignment'], $f['round'])['photoGroups'];
        $this->assertCount(2, $groups);
        $this->assertContains([$copy->id], $groups);
        $this->assertContains([$f['photo']->id, $f['second']->id], $groups);
    }

    public function test_changing_group_context_invalidates_the_old_jury_form(): void
    {
        $f = $this->seriesFixture();
        $data = app(JuryEvaluationService::class)->evaluationData($f['assignment'], $f['round']);
        $f['submission']->category->update(['photos_grouped' => false]);
        $this->actingAs($f['juror'], 'juri')->put(route('juri.evaluations.save', [$f['competition'], $f['submission']->category]), ['evaluation_context' => $data['evaluationContext'], 'scores' => [$f['photo']->id => [$f['criterion']->id => 8]]])->assertSessionHasErrors('scores');
        $this->assertDatabaseCount('jury_scores', 0);
    }

    public function test_category_mode_change_with_an_existing_submission_rolls_back_other_edits(): void
    {
        $f = $this->seriesFixture();
        try {
            (new Step6)->persist($f['competition'], ['categories' => [['id' => $f['submission']->competition_category_id, 'photos_grouped' => false, 'max_photos_per_participant' => 18]]]);
            $this->fail('Expected locked mode');
        } catch (ValidationException) {
            $category = $f['submission']->category->fresh();
            $this->assertTrue($category->photos_grouped);
            $this->assertSame(4, $category->max_photos_per_participant);
        }
    }

    public function test_series_identity_cannot_be_changed_after_creation(): void
    {
        $f = $this->seriesFixture();
        $this->expectException(\LogicException::class);
        $f['submission']->forceFill(['series_code' => str_repeat('A', 16)])->save();
    }

    public function test_withdrawing_a_photo_keeps_series_identity_and_removes_it_from_jury_group(): void
    {
        $f = $this->seriesFixture();
        $code = $f['submission']->seriesCode();
        app(CompetitionSubmissionPhotoService::class)->remove($f['second']);
        $this->assertSame($code, $f['submission']->fresh()->seriesCode());
        $this->assertSame([[$f['photo']->id]], app(JuryEvaluationService::class)->evaluationData($f['assignment'], $f['round'])['photoGroups']);
    }

    public function test_portfolio_source_and_file_deletion_do_not_delete_series_copy(): void
    {
        $submission = $this->securitySubmission();
        $submission->category->update(['photos_grouped' => true]);
        $source = Photo::factory()->create(['user_id' => $submission->entry->user_id]);
        Storage::disk('public')->put($source->disk_path, file_get_contents(base_path('tests/Fixtures/identity-metadata.jpg')));
        $copy = app(CompetitionSubmissionPhotoService::class)->fromPortfolio($submission, $source, null, []);
        $code = $submission->seriesCode();
        Storage::disk('public')->delete($source->disk_path);
        $source->delete();
        $this->assertSame($code, $submission->fresh()->seriesCode());
        $this->assertSame($submission->id, $copy->fresh()->competition_submission_id);
        Storage::disk('local')->assertExists($copy->disk_path);
        Storage::disk('local')->assertExists($copy->jury_path);
    }

    public function test_series_identity_story_and_order_are_frozen_in_member_archive(): void
    {
        $f = $this->resultFixture();
        $f['submission']->category->update(['photos_grouped' => true]);
        $f['submission']->update(['category_story' => 'Published series story']);
        $f['photo']->update(['sort_order' => 30]);
        $f['otherPhoto']->update(['sort_order' => 10]);
        app(CompetitionResultService::class)->aggregate($f['round']);
        $this->get(route('eys.competitions.show', $f['competition']))->assertOk()->assertSee($f['submission']->seriesCode());
        $this->put(route('eys.competitions.save-result-awards', $f['competition']), ['result_context' => $this->resultContextFor($f['competition']), 'award_assignments' => [$f['award']->id => [1 => $f['result']->id]]])->assertSessionHasNoErrors();
        $this->post(route('eys.competitions.publish-results', $f['competition']), ['result_context' => $this->resultContextFor($f['competition'])])->assertSessionHasNoErrors();
        $entry = $f['submission']->entry;
        $owner = $entry->user;
        $code = $f['submission']->seriesCode();
        $archive = app(MemberResultArchiveService::class)->forMember($f['competition'], $owner);
        $this->assertSame([$f['otherPhoto']->id, $f['photo']->id], $archive['photos']->pluck('photo_id')->all());
        $this->assertSame([$code], $archive['photos']->pluck('series_code')->unique()->all());
        $entry->delete();
        $response = $this->actingAs($owner, 'web')->get(route('competitions.results.mine', $f['competition']));
        $response->assertOk()->assertSee($code)->assertSee('Published series story');
        $this->get(route('result.competitions.show', $f['competition']))->assertOk()->assertSee($code)->assertDontSee('Published series story');
    }

    public function test_non_grouped_categories_keep_individual_display_and_no_series_in_snapshot(): void
    {
        $f = $this->evaluationFixture();
        $this->assertSame([], $f['data']['photoGroups']);
        $this->actingAs($f['juror'], 'juri')->get(route('juri.evaluations.show', [$f['competition'], $f['submission']->category]))->assertOk()->assertDontSee($f['submission']->seriesCode());
        $context = app(CompetitionRegulationContextBuilder::class)->build($f['competition'], 'en');
        $this->assertFalse($context['categories'][0]['photos_grouped']);
    }
}
