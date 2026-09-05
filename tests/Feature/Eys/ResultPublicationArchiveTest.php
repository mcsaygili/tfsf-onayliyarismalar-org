<?php

namespace Tests\Feature\Eys;

use App\Models\Competition;
use App\Models\CompetitionResultPublication;
use App\Models\EysUser;
use App\Services\CompetitionAuditService;
use App\Services\CompetitionResultService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\CreatesResultSelection;
use Tests\TestCase;

class ResultPublicationArchiveTest extends TestCase
{
    use CreatesResultSelection, RefreshDatabase;

    private function publish(array $f, array $input = []): CompetitionResultPublication
    {
        Notification::fake();
        app(CompetitionResultService::class)->aggregate($f['round']);
        $this->put(route('eys.competitions.save-result-awards', $f['competition']), ['result_context' => $this->resultContextFor($f['competition']), 'award_assignments' => [$f['award']->id => [1 => $f['result']->id]]])->assertSessionHasNoErrors();
        $this->post(route('eys.competitions.publish-results', $f['competition']), ['result_context' => $this->resultContextFor($f['competition']), ...$input])->assertSessionHasNoErrors();

        return $f['competition']->resultPublications()->firstOrFail();
    }

    public function test_public_page_search_and_csv_preserve_announced_names_after_live_data_changes(): void
    {
        $f = $this->resultFixture();
        $member = $f['submission']->entry->user;
        $member->update(['first_name' => '=Published Person', 'last_name' => 'Original']);
        $f['competition']->upsertTranslations(['en' => ['name' => 'Published Competition', 'subject' => 'Published Subject']]);
        $f['competition']->institution->update(['name' => 'Published Institution']);
        $f['submission']->category->upsertTranslations(['en' => ['name' => 'Published Category']]);
        $publication = $this->publish($f);
        $before = $publication->snapshot;
        $member->update(['first_name' => 'Changed Person']);
        $f['competition']->upsertTranslations(['en' => ['name' => 'Changed Competition', 'subject' => 'Changed Subject']]);
        $f['competition']->institution->update(['name' => 'Changed Institution']);
        $f['submission']->category->upsertTranslations(['en' => ['name' => 'Changed Category']]);
        $f['award']->awardReference->upsertTranslations(['en' => ['name' => 'Changed Award']]);
        $f['result']->update(['rank' => 999]);
        $this->get(route('result.competitions.show', $f['competition']))->assertOk()
            ->assertSee('Published Competition')->assertSee('Published Subject')->assertSee('Published Institution')->assertSee('Published Category')->assertSee('=Published Person Original')->assertSee('First Prize')->assertDontSee('Changed')->assertDontSee('#999');
        $this->get(route('result.index', ['q' => 'Published Competition']))->assertSee('Published Competition');
        $this->get(route('result.index', ['q' => 'Changed Competition']))->assertDontSee('Published Competition');
        $csv = $this->get(route('eys.competitions.reports.results', $f['competition']))->assertOk()->streamedContent();
        $this->assertStringContainsString("'=Published Person Original", $csv);
        $this->assertStringNotContainsString('Changed', $csv);
        $this->assertSame($before, $publication->refresh()->snapshot);
    }

    public function test_frozen_image_and_identity_survive_source_photo_and_member_changes(): void
    {
        $f = $this->resultFixture();
        $publication = $this->publish($f);
        $asset = $publication->assets()->where('source_photo_id', $f['photo']->id)->sole();
        $bytes = Storage::disk('local')->get($asset->disk_path);
        Storage::disk('local')->put($f['photo']->jury_path, 'replaced-live-image');
        $f['photo']->delete();
        $this->get(route('result.publications.photos.show', [$publication, $asset->source_photo_id]))->assertOk()->assertStreamedContent($bytes)->assertHeader('Cache-Control', 'no-store, private');
        $this->get(route('result.photos.show', $asset->source_photo_id))->assertOk()->assertStreamedContent($bytes);
        $this->get(route('result.competitions.show', $f['competition']))->assertOk()->assertSee($publication->snapshot['results'][0]['participant']);
    }

    public function test_withdrawal_closes_public_assets_and_republication_preserves_old_version_for_reviewers(): void
    {
        $f = $this->resultFixture();
        $first = $this->publish($f, ['publication_note' => 'First release']);
        $asset = $first->assets()->where('source_photo_id', $f['photo']->id)->sole();
        $oldSnapshot = $first->snapshot;
        $this->post(route('eys.competitions.unpublish-results', $f['competition']), ['reason' => 'Correction of award announcement.'])->assertSessionHasNoErrors();
        $this->get(route('result.competitions.show', $f['competition']))->assertNotFound();
        $this->get(route('result.publications.photos.show', [$first, $asset->source_photo_id]))->assertNotFound();
        $this->get(route('eys.competitions.preview-results', [$f['competition'], 'version' => 1]))->assertOk()->assertSee('v1');
        $this->get(route('eys.competitions.publication-photos.show', [$f['competition'], $first, $asset->source_photo_id]))->assertOk();
        $f['submission']->entry->user->update(['first_name' => 'Corrected Name']);
        $second = $this->publish($f, ['publication_note' => 'Second release']);
        $this->assertSame(2, $second->version);
        $this->assertSame($oldSnapshot, $first->refresh()->snapshot);
        $this->get(route('result.competitions.show', $f['competition']))->assertOk()->assertSee('Corrected Name')->assertSee('Second release');
        $this->get(route('result.publications.photos.show', [$first, $asset->source_photo_id]))->assertNotFound();
        $this->get(route('result.photos.show', $asset->source_photo_id))->assertOk();
        $this->get(route('eys.competitions.preview-results', [$f['competition'], 'version' => 1]))->assertOk()->assertDontSee('Corrected Name');
    }

    public function test_scheduled_publication_is_hidden_until_its_time_and_does_not_leak_future_history(): void
    {
        $f = $this->resultFixture();
        $publication = $this->publish($f, ['publish_at' => now()->addHour()->toDateTimeString(), 'publication_note' => 'Future secret note']);
        $url = route('result.publications.photos.show', [$publication, $f['photo']->id]);
        $this->get(route('result.index'))->assertDontSee($f['competition']->name);
        $this->get(route('result.competitions.show', $f['competition']))->assertNotFound();
        $this->get($url)->assertNotFound();
        $this->travel(61)->minutes();
        $this->get(route('result.competitions.show', $f['competition']))->assertOk()->assertSee('Future secret note');
        $this->get($url)->assertOk();
    }

    public function test_future_history_and_suspended_competition_do_not_expose_archives(): void
    {
        $f = $this->resultFixture();
        $publication = $this->publish($f);
        $future = $f['competition']->resultPublications()->create(['version' => 99, 'snapshot' => $publication->snapshot, 'published_at' => now()->addWeek(), 'publication_note' => 'Not public future note']);
        $this->get(route('result.competitions.show', $f['competition']))->assertOk()->assertDontSee('Not public future note');
        $f['competition']->forceFill(['publication_state' => 'suspended'])->save();
        $this->get(route('result.competitions.show', $f['competition']))->assertNotFound();
        $this->get(route('result.publications.photos.show', [$publication, $f['photo']->id]))->assertNotFound();
        $this->post(route('eys.competitions.unpublish-results', $f['competition']), ['reason' => 'Withdraw the current announcement.'])->assertSessionHasNoErrors();
        $this->assertNotNull($publication->refresh()->withdrawn_at);
        $this->assertNull($future->refresh()->withdrawn_at);
    }

    public function test_missing_safe_image_blocks_publication_without_partial_rows_or_files(): void
    {
        $f = $this->resultFixture();
        $this->put(route('eys.competitions.save-result-awards', $f['competition']), $this->awardPayload($f))->assertSessionHasNoErrors();
        Storage::disk('local')->delete($f['photo']->jury_path);
        $this->post(route('eys.competitions.publish-results', $f['competition']), ['result_context' => $this->resultContextFor($f['competition'])])->assertSessionHasErrors('results');
        $this->assertDatabaseCount('competition_result_publications', 0);
        $this->assertDatabaseCount('competition_result_assets', 0);
        $this->assertNull($f['competition']->fresh()->results_published_at);
        $this->assertSame([], Storage::disk('local')->allFiles('result-publications'));
    }

    public function test_audit_failure_rolls_back_database_and_frozen_files(): void
    {
        $f = $this->resultFixture();
        $this->put(route('eys.competitions.save-result-awards', $f['competition']), $this->awardPayload($f))->assertSessionHasNoErrors();
        $this->mock(CompetitionAuditService::class)->shouldReceive('record')->andThrow(new \RuntimeException('Injected publication audit failure'));
        $this->post(route('eys.competitions.publish-results', $f['competition']), ['result_context' => $this->resultContextFor($f['competition'])])->assertStatus(500);
        $this->assertDatabaseCount('competition_result_publications', 0);
        $this->assertDatabaseCount('competition_result_assets', 0);
        $this->assertSame([], Storage::disk('local')->allFiles('result-publications'));
    }

    public function test_corrupt_archived_image_is_rejected_even_when_source_exists(): void
    {
        $f = $this->resultFixture();
        $publication = $this->publish($f);
        $asset = $publication->assets()->where('source_photo_id', $f['photo']->id)->sole();
        Storage::disk('local')->put($asset->disk_path, 'corrupt-archive');
        $this->get(route('result.publications.photos.show', [$publication, $f['photo']->id]))->assertNotFound();
        $this->get(route('eys.competitions.publication-photos.show', [$f['competition'], $publication, $f['photo']->id]))->assertNotFound();
        $this->artisan('tfsf:audit-result-archives', ['--verify-files' => true])->expectsOutputToContain('checksum_mismatch')->assertFailed();
    }

    public function test_historical_preview_requires_permission_and_cannot_cross_competitions(): void
    {
        $f = $this->resultFixture();
        $publication = $this->publish($f);
        $other = Competition::factory()->create();
        $this->get(route('eys.competitions.publication-photos.show', [$other, $publication, $f['photo']->id]))->assertNotFound();
        $this->actingAs(EysUser::factory()->create(), 'eys');
        $this->get(route('eys.competitions.preview-results', [$f['competition'], 'version' => 1]))->assertForbidden();
        $this->get(route('eys.competitions.publication-photos.show', [$f['competition'], $publication, $f['photo']->id]))->assertForbidden();
    }

    public function test_legacy_snapshot_displays_only_recorded_values_and_missing_archive_is_reported(): void
    {
        $f = $this->resultFixture();
        $f['competition']->forceFill(['results_published_at' => now(), 'results_publication_version' => 1])->save();
        $this->get(route('result.competitions.show', $f['competition']))->assertNotFound();
        $this->artisan('tfsf:audit-result-archives')->expectsOutputToContain('missing_publication')->assertFailed();
        $f['competition']->resultPublications()->create(['version' => 1, 'published_at' => now(), 'snapshot' => [
            'competition' => ['name' => ['en' => 'Legacy Recorded Title'], 'institution' => 'Legacy Institution'],
            'round' => ['name' => 'Legacy Final'],
            'results' => [['photo_id' => $f['photo']->id, 'category_id' => $f['submission']->competition_category_id, 'category' => ['en' => 'Legacy Category'], 'rank' => 1, 'average_score' => 9, 'participant' => 'Legacy Person', 'awards' => [['name' => ['en' => 'Legacy Award']]]]],
        ]]);
        $this->get(route('result.competitions.show', $f['competition']))->assertOk()->assertSee('Legacy Person')->assertSee('Legacy Recorded Title')->assertSee(__('result.archive_partial'))->assertSee(__('result.image_unavailable'));
        $this->get(route('result.photos.show', $f['photo']->id))->assertNotFound();
        $this->artisan('tfsf:audit-result-archives')->expectsOutputToContain('legacy_partial_snapshot')->assertFailed();
    }

    public function test_published_snapshot_cannot_be_edited_in_place(): void
    {
        $f = $this->resultFixture();
        $publication = $this->publish($f);
        $this->expectException(\LogicException::class);
        $publication->update(['snapshot' => ['changed' => true]]);
    }

    public function test_competition_deletion_cannot_cascade_away_publication_history(): void
    {
        $f = $this->resultFixture();
        $this->publish($f);
        $this->expectException(QueryException::class);
        DB::table('competitions')->where('id', $f['competition']->id)->delete();
    }
}
