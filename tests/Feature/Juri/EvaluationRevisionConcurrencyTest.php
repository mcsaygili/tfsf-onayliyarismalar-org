<?php

namespace Tests\Feature\Juri;

use App\Enums\Module;
use App\Models\EysUser;
use App\Models\Permission;
use App\Services\CompetitionResultService;
use App\Services\JuryEvaluationService;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group;
use Spatie\Permission\PermissionRegistrar;
use Tests\Concerns\CreatesEvaluationRevision;
use Tests\Concerns\RunsConcurrentAccountRequests;
use Tests\TestCase;

#[Group('mysql-concurrency')]
class EvaluationRevisionConcurrencyTest extends TestCase
{
    use CreatesEvaluationRevision, DatabaseMigrations, RunsConcurrentAccountRequests, \Tests\Concerns\ReadsResultContext;

    protected function beforeRefreshingDatabase(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            $this->markTestSkipped('Requires MariaDB/MySQL process isolation.');
        }
        $this->assertSame('tfsf_testing', DB::connection()->getDatabaseName());
        $this->assertTrue(app()->environment('testing'));
    }

    private function finalizeRequest(array $f): array
    {
        return ['guard' => 'juri', 'user_id' => $f['juror']->id, 'method' => 'PUT',
            'url' => route('juri.evaluations.finalize', [$f['competition'], $f['submission']->category]),
            'payload' => ['evaluation_context' => $f['data']['evaluationContext'], 'scores' => [$f['photo']->id => [$f['criterion']->id => 7]]]];
    }

    public function test_two_simultaneous_finalizations_complete_only_once(): void
    {
        $f = $this->evaluationFixture();
        $results = $this->simultaneousRequests($this->finalizeRequest($f));
        $this->assertSame([302, 302], array_column($results, 'status'));
        $this->assertSame(1, count(array_filter(array_column($results, 'errors'))));
        $this->assertDatabaseCount('jury_evaluation_submissions', 1);
        $this->assertDatabaseCount('jury_scores', 1);
        $this->assertSame(1, $f['assignment']->fresh()->evaluation_version);
        $this->assertSame(1, DB::table('competition_status_logs')->where('action', 'jury_evaluation_finalized')->count());
    }

    public function test_member_revision_and_jury_finalization_cannot_leave_stale_final_scores(): void
    {
        $f = $this->evaluationFixture();
        $results = $this->simultaneousRequests($this->finalizeRequest($f), [
            'guard' => 'web', 'user_id' => $f['submission']->entry->user_id, 'method' => 'PUT',
            'url' => route('competitions.submission.details.update', $f['submission']),
            'payload' => ['details_version' => 0, 'category_story' => 'Yeni açıklama',
                'photos' => [['id' => $f['photo']->id, ...$f['photo']->declarationData(), 'title' => 'Yeni eser adı']]],
        ]);
        $this->assertSame([302, 302], array_column($results, 'status'));
        $this->assertEmpty($results[1]['errors']);
        $this->assertSame('Yeni eser adı', $f['photo']->fresh()->declarationData()['title']);
        $this->assertSame(1, $f['submission']->fresh()->details_version);
        $this->assertDatabaseCount('jury_evaluation_submissions', 0);
        $this->assertSame(0, DB::table('jury_scores')->whereNotNull('submitted_at')->count());
        $this->assertSame($results[0]['errors'] ? 1 : 2, $f['assignment']->fresh()->evaluation_version);
    }

    public function test_final_round_creation_and_member_revision_cannot_both_succeed(): void
    {
        $f = $this->evaluationFixture();
        app(JuryEvaluationService::class)->save($f['assignment'], $f['round'], [$f['photo']->id => [$f['criterion']->id => 7]], $f['data']['evaluationContext'], true);
        app(CompetitionResultService::class)->aggregate($f['round']);
        $result = $f['round']->results()->sole();
        $reviewer = EysUser::factory()->create();
        app(PermissionRegistrar::class)->setPermissionsTeamId(Module::Institution->value);
        Permission::firstOrCreate(['name' => 'institution.competitions.manage', 'guard_name' => 'eys']);
        $reviewer->givePermissionTo('institution.competitions.manage');
        $this->actingAs($reviewer, 'eys');
        $context = $this->resultContextFor($f['competition']);
        $results = $this->simultaneousRequests([
            'guard' => 'eys', 'user_id' => $reviewer->id, 'method' => 'POST',
            'url' => route('eys.competitions.create-final-round', $f['competition']),
            'payload' => ['result_context' => $context, 'photo_result_ids' => [$result->id]],
        ], [
            'guard' => 'web', 'user_id' => $f['submission']->entry->user_id, 'method' => 'PUT',
            'url' => route('competitions.submission.details.update', $f['submission']),
            'payload' => ['details_version' => 0, 'category_story' => 'Yeni açıklama',
                'photos' => [['id' => $f['photo']->id, ...$f['photo']->declarationData(), 'title' => 'Yeni eser adı']]],
        ]);
        $this->assertSame([302, 302], array_column($results, 'status'));
        $this->assertSame(1, count(array_filter(array_column($results, 'errors'))));
        if ($results[0]['errors']) {
            $this->assertSame(0, $f['competition']->evaluationRounds()->where('is_final', true)->count());
            $this->assertDatabaseCount('jury_evaluation_submissions', 0);
            $this->assertSame('Yeni eser adı', $f['photo']->fresh()->declarationData()['title']);
        } else {
            $this->assertSame(1, $f['competition']->evaluationRounds()->where('is_final', true)->count());
            $this->assertDatabaseCount('jury_evaluation_submissions', 1);
            $this->assertSame('Eser', $f['photo']->fresh()->declarationData()['title']);
        }
    }
}
