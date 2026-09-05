<?php

namespace Tests\Feature\Eys;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group;
use Tests\Concerns\CreatesResultSelection;
use Tests\Concerns\RunsConcurrentAccountRequests;
use Tests\TestCase;

#[Group('mysql-concurrency')]
class ResultSelectionConcurrencyTest extends TestCase
{
    use CreatesResultSelection, DatabaseMigrations, RunsConcurrentAccountRequests;

    protected function beforeRefreshingDatabase(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            $this->markTestSkipped('Requires MariaDB/MySQL process isolation.');
        }
        $this->assertSame('tfsf_testing', DB::connection()->getDatabaseName());
        $this->assertTrue(app()->environment('testing'));
    }

    private function awardRequest(array $f, ?string $resultId = null): array
    {
        return ['storage_root' => $f['storageRoot'], 'guard' => 'eys', 'user_id' => $f['reviewer']->id, 'method' => 'PUT',
            'url' => route('eys.competitions.save-result-awards', $f['competition']), 'payload' => $this->awardPayload($f, $resultId)];
    }

    private function readyToPublish(): array
    {
        $f = $this->resultFixture();
        $this->put(route('eys.competitions.save-result-awards', $f['competition']), $this->awardPayload($f))->assertSessionHasNoErrors();
        $f['context'] = $this->resultContextFor($f['competition']);

        return $f;
    }

    private function publishRequest(array $f): array
    {
        return ['storage_root' => $f['storageRoot'], 'guard' => 'eys', 'user_id' => $f['reviewer']->id, 'method' => 'POST',
            'url' => route('eys.competitions.publish-results', $f['competition']), 'payload' => ['result_context' => $f['context']]];
    }

    public function test_two_award_forms_cannot_overwrite_the_same_slot(): void
    {
        $f = $this->resultFixture();
        $results = $this->simultaneousRequests($this->awardRequest($f), $this->awardRequest($f, $f['otherResult']->id));
        $this->assertSame([302, 302], array_column($results, 'status'));
        $this->assertSame(1, count(array_filter(array_column($results, 'errors'))));
        $this->assertDatabaseCount('competition_result_awards', 1);
        $this->assertDatabaseHas('competition_result_awards', ['competition_photo_result_id' => $results[0]['errors'] ? $f['otherResult']->id : $f['result']->id]);
    }

    public function test_publication_and_award_change_cannot_both_succeed(): void
    {
        $f = $this->readyToPublish();
        $results = $this->simultaneousRequests($this->publishRequest($f), $this->awardRequest($f, $f['otherResult']->id));
        $success = array_map(fn ($result) => $result['status'] === 302 && ! $result['errors'], $results);
        $this->assertSame(1, count(array_filter($success)));
        $this->assertDatabaseCount('competition_result_publications', $success[0] ? 1 : 0);
        $this->assertDatabaseHas('competition_result_awards', ['competition_photo_result_id' => $success[0] ? $f['result']->id : $f['otherResult']->id]);
        $this->assertSame($success[0], $f['competition']->fresh()->results_published_at !== null);
    }

    public function test_publication_and_member_revision_cannot_publish_stale_content(): void
    {
        $f = $this->readyToPublish();
        $results = $this->simultaneousRequests($this->publishRequest($f), [
            'guard' => 'web', 'user_id' => $f['submission']->entry->user_id, 'method' => 'PUT',
            'url' => route('competitions.submission.details.update', $f['submission']),
            'payload' => ['details_version' => $f['submission']->details_version, 'category_story' => 'Yeni kategori hikâyesi',
                'photos' => [['id' => $f['photo']->id, ...$f['photo']->declarationData(), 'title' => 'Yeni ad'], ['id' => $f['otherPhoto']->id, ...$f['otherPhoto']->declarationData()]]],
        ]);
        $this->assertSame([302, 302], array_column($results, 'status'));
        $this->assertSame(1, count(array_filter(array_column($results, 'errors'))));
        $published = ! $results[0]['errors'];
        $this->assertDatabaseCount('competition_result_publications', $published ? 1 : 0);
        $this->assertSame($published ? 'Eser' : 'Yeni ad', $f['photo']->fresh()->declarationData()['title']);
        $this->assertSame($published, $f['competition']->fresh()->results_published_at !== null);
    }
}
