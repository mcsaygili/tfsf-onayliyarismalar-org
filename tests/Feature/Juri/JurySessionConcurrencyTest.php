<?php

namespace Tests\Feature\Juri;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group;
use Tests\Concerns\CreatesJurySession;
use Tests\Concerns\RunsConcurrentAccountRequests;
use Tests\TestCase;

#[Group('mysql-concurrency')]
class JurySessionConcurrencyTest extends TestCase
{
    use CreatesJurySession, DatabaseMigrations, RunsConcurrentAccountRequests;

    protected function beforeRefreshingDatabase(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            $this->markTestSkipped('Requires MariaDB/MySQL process isolation.');
        }
        $this->assertSame('tfsf_testing', DB::connection()->getDatabaseName());
        $this->assertTrue(app()->environment('testing'));
    }

    private function updateRequest(array $f, array $overrides = []): array
    {
        return ['guard' => 'eys', 'user_id' => $f['reviewer']->id, 'method' => 'PUT',
            'url' => route('eys.competitions.jury-session.update', $f['competition']),
            'payload' => $this->sessionPayload($f, $overrides)];
    }

    public function test_two_session_editors_cannot_overwrite_each_other(): void
    {
        $f = $this->sessionFixture();
        $results = $this->simultaneousRequests($this->updateRequest($f, ['action' => 'save', 'location' => 'Birinci salon']), $this->updateRequest($f, ['action' => 'save', 'location' => 'İkinci salon']));
        $this->assertSame([302, 302], array_column($results, 'status'));
        $this->assertSame(1, count(array_filter(array_column($results, 'errors'))));
        $this->assertSame($results[0]['errors'] ? 'İkinci salon' : 'Birinci salon', $f['session']->fresh()->location);
        $this->assertSame($f['session']->version + 1, $f['session']->fresh()->version);
    }

    public function test_conflict_declaration_and_closure_cannot_both_succeed(): void
    {
        $f = $this->sessionFixture();
        $results = $this->simultaneousRequests($this->updateRequest($f), [
            'guard' => 'juri', 'user_id' => $f['juror']->id, 'method' => 'POST',
            'url' => route('juri.sessions.declaration', $f['competition']),
            'payload' => ['session_version' => $f['session']->version, 'conflict_declared' => true, 'conflict_note' => 'Tarafsızlığımı etkileyen ilişki'],
        ]);
        $this->assertSame([302, 302], array_column($results, 'status'));
        $this->assertSame(1, count(array_filter(array_column($results, 'errors'))));
        $this->assertSame($results[0]['errors'] ? 'open' : 'closed', $f['session']->fresh()->status);
        $this->assertSame((bool) $results[0]['errors'], $f['attendance']->fresh()->conflict_declared);
        $this->assertSame($f['session']->version + 1, $f['session']->fresh()->version);
    }

    public function test_committee_decision_and_closure_share_the_session_version(): void
    {
        $f = $this->sessionFixture();
        $results = $this->simultaneousRequests($this->updateRequest($f), [
            'guard' => 'eys', 'user_id' => $f['reviewer']->id, 'method' => 'PUT',
            'url' => route('eys.competitions.save-final-round', $f['competition']),
            'payload' => ['session_version' => $f['session']->version, 'decisions' => [$f['decision']->id => ['decision' => 'selected', 'rank' => 1, 'score' => 8]]],
        ]);
        $this->assertSame([302, 302], array_column($results, 'status'));
        $this->assertSame(1, count(array_filter(array_column($results, 'errors'))));
        $this->assertSame($results[0]['errors'] ? 'open' : 'closed', $f['session']->fresh()->status);
        $this->assertSame($results[0]['errors'] ? 8 : 7, $f['decision']->fresh()->score);
        $this->assertSame($f['session']->version + 1, $f['session']->fresh()->version);
    }
}
