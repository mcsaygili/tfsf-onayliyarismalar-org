<?php

namespace Tests\Feature;

use App\Models\CompetitionRegistration;
use App\Models\User;
use App\Services\CompetitionRegistrationService;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group;
use Tests\Concerns\CreatesCompetitionRegistration;
use Tests\Concerns\RunsConcurrentAccountRequests;
use Tests\TestCase;

#[Group('mysql-concurrency')]
class CompetitionRegistrationConcurrencyTest extends TestCase
{
    use CreatesCompetitionRegistration, DatabaseMigrations, RunsConcurrentAccountRequests;

    protected function beforeRefreshingDatabase(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            $this->markTestSkipped('Registration concurrency requires MariaDB.');
        }
        $this->assertSame('tfsf_testing', DB::connection()->getDatabaseName());
        $this->assertTrue(app()->environment('testing'));
    }

    public function test_simultaneous_members_receive_distinct_consecutive_registration_numbers(): void
    {
        $f = $this->registrationFixture(0);
        $a = User::factory()->create(['date_of_birth' => '1990-01-01', 'gender' => 'male']);
        $b = User::factory()->create(['date_of_birth' => '1990-01-01', 'gender' => 'male']);
        $base = ['guard' => 'web', 'method' => 'POST', 'url' => route('competitions.registration.store', $f['competition']), 'payload' => []];
        $results = $this->simultaneousRequests($base + ['user_id' => $a->id], $base + ['user_id' => $b->id]);
        $this->assertSame([302, 302], array_column($results, 'status'));
        $this->assertSame([false, false], array_column($results, 'errors'));
        $this->assertSame([1, 2, 3], CompetitionRegistration::orderBy('number')->pluck('number')->all());
        $this->assertSame(3, $f['competition']->fresh()->registration_sequence);
    }

    public function test_double_registration_request_produces_one_number_and_event(): void
    {
        $f = $this->registrationFixture(0);
        $member = User::factory()->create(['date_of_birth' => '1990-01-01', 'gender' => 'male']);
        $results = $this->simultaneousRequests(['guard' => 'web', 'user_id' => $member->id, 'method' => 'POST', 'url' => route('competitions.registration.store', $f['competition']), 'payload' => []]);
        $this->assertSame([302, 302], array_column($results, 'status'));
        $this->assertSame([false, false], array_column($results, 'errors'));
        $registration = CompetitionRegistration::where('user_id', $member->id)->sole();
        $this->assertSame(2, $registration->number);
        $this->assertSame(1, $registration->events()->count());
        $this->assertSame(2, $f['competition']->fresh()->registration_sequence);
    }

    public function test_two_reviewers_cannot_overwrite_each_others_decision(): void
    {
        $f = $this->registrationFixture(0);
        app(CompetitionRegistrationService::class)->submit($f['registration'], $f['member'], 1);
        $base = ['guard' => 'institution', 'user_id' => $f['staff']->id, 'method' => 'POST', 'url' => route('institution.registrations.decide', $f['registration'])];
        $results = $this->simultaneousRequests($base + ['payload' => ['version' => 2, 'decision' => 'approved']], $base + ['payload' => ['version' => 2, 'decision' => 'rejected', 'note' => 'Eligibility mismatch.']]);
        $this->assertSame([302, 302], array_column($results, 'status'));
        $this->assertSame(1, count(array_filter(array_column($results, 'errors'))));
        $this->assertSame(3, $f['registration']->fresh()->version);
        $this->assertSame(1, $f['registration']->events()->whereIn('event', ['approved', 'rejected'])->count());
        $this->assertSame($results[0]['errors'] ? 'rejected' : 'approved', $f['registration']->fresh()->status);
    }
}
