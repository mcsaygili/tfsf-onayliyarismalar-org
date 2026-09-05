<?php

namespace Tests\Feature;

use App\Models\CompetitionRegistration;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group;
use Tests\Concerns\CreatesRegistrationException;
use Tests\Concerns\RunsConcurrentAccountRequests;
use Tests\TestCase;

#[Group('mysql-concurrency')]
class RegistrationExceptionConcurrencyTest extends TestCase
{
    use CreatesRegistrationException, DatabaseMigrations, RunsConcurrentAccountRequests;

    protected function beforeRefreshingDatabase(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            $this->markTestSkipped('Requires MariaDB process isolation.');
        }
        $this->assertSame('tfsf_testing', DB::connection()->getDatabaseName());
        $this->assertTrue(app()->environment('testing'));
    }

    public function test_simultaneous_direct_approvals_cannot_create_duplicate_registration_or_overwrite_reason(): void
    {
        $f = $this->exceptionFixture();
        $member = User::factory()->create(['date_of_birth' => '1990-01-01', 'gender' => 'male']);
        $base = ['guard' => 'institution', 'user_id' => $f['staff']->id, 'method' => 'POST', 'url' => route('institution.registrations.direct.store', $f['competition'])];
        $a = $this->exceptionPayload($f, ['user_id' => $member->id, 'version' => 0, 'reason' => 'First reviewer evidence checked.']);
        $b = array_replace($a, ['reason' => 'Second reviewer evidence checked.']);
        $results = $this->simultaneousRequests($base + ['payload' => $a], $base + ['payload' => $b]);
        $this->assertSame([302, 302], array_column($results, 'status'));
        $this->assertSame(1, count(array_filter(array_column($results, 'errors'))));
        $registration = CompetitionRegistration::where('user_id', $member->id)->sole();
        $this->assertSame(2, $registration->number);
        $this->assertSame(2, $f['competition']->fresh()->registration_sequence);
        $this->assertSame(1, $registration->events()->where('event', 'exception_approved')->count());
        $this->assertSame($results[0]['errors'] ? $b['reason'] : $a['reason'], $registration->review_note);
    }

    public function test_permission_revocation_and_direct_approval_serialize_on_competition(): void
    {
        $f = $this->exceptionFixture();
        $approve = ['guard' => 'institution', 'user_id' => $f['staff']->id, 'method' => 'POST', 'url' => route('institution.registrations.direct.store', $f['competition']), 'payload' => $this->exceptionPayload($f)];
        $revoke = ['guard' => 'eys', 'user_id' => $f['manager']->id, 'method' => 'POST', 'url' => route('eys.competitions.registration-permissions.store', $f['competition']),
            'payload' => ['actor_type' => 'institution', 'actor_id' => $f['staff']->id, 'version' => 1, 'active' => 0, 'reason' => 'Concurrent permission revocation.']];
        $results = $this->simultaneousRequests($approve, $revoke);
        $this->assertSame(302, $results[1]['status']);
        $this->assertFalse($results[1]['errors']);
        $this->assertContains($results[0]['status'], [302, 404]);
        $this->assertFalse($f['grant']->fresh()->active);
        $approved = $f['registration']->fresh()->status === 'approved';
        $this->assertSame($approved ? 302 : 404, $results[0]['status']);
        $this->assertSame($approved ? 1 : 0, $f['registration']->events()->where('event', 'exception_approved')->count());
        $this->actingAs($f['staff'], 'institution')->post($approve['url'], $approve['payload'])->assertNotFound();
    }
}
