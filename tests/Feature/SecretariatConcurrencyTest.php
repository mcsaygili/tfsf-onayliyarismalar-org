<?php
namespace Tests\Feature;

use App\Models\InstitutionStaff;
use App\Services\CompetitionRegistrationService;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group;
use Tests\Concerns\CreatesSecretariat;
use Tests\Concerns\RunsConcurrentAccountRequests;
use Tests\TestCase;

#[Group('mysql-concurrency')]
class SecretariatConcurrencyTest extends TestCase
{
    use CreatesSecretariat, DatabaseMigrations, RunsConcurrentAccountRequests;

    protected function beforeRefreshingDatabase(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            $this->markTestSkipped('Requires MariaDB process isolation.');
        }
        $this->assertSame('tfsf_testing', DB::connection()->getDatabaseName());
        $this->assertTrue(app()->environment('testing'));
    }

    private function assignmentRequest(array $f, ?string $id): array
    {
        return ['guard' => 'eys', 'user_id' => $f['manager']->id, 'method' => 'POST', 'url' => route('eys.competitions.secretariat.store', $f['competition']), 'payload' => ['account_id' => $id, 'version' => 1, 'reason' => 'Concurrent secretary assignment.']];
    }

    public function test_two_assignment_forms_cannot_overwrite_each_other(): void
    {
        $f = $this->secretariatFixture();
        $other = InstitutionStaff::factory()->create(['account_kind' => 'secretariat', 'institution_id' => null]);
        $results = $this->simultaneousRequests($this->assignmentRequest($f, $other->id), $this->assignmentRequest($f, null));
        $this->assertSame([302, 302], array_column($results, 'status'));
        $this->assertSame(1, count(array_filter(array_column($results, 'errors'))));
        $this->assertSame(2, $f['competition']->fresh()->secretariat_version);
        $this->assertSame($results[0]['errors'] ? null : $other->id, $f['competition']->fresh()->secretariat_id);
    }

    public function test_removal_and_registration_approval_have_one_serial_order(): void
    {
        $f = $this->secretariatFixture();
        app(CompetitionRegistrationService::class)->submit($f['registration'], $f['member'], 1);
        $approve = ['guard' => 'institution', 'user_id' => $f['secretariat']->id, 'method' => 'POST', 'url' => route('institution.registrations.decide', $f['registration']), 'payload' => ['version' => 2, 'decision' => 'approved']];
        $results = $this->simultaneousRequests($this->assignmentRequest($f, null), $approve);
        $this->assertSame(302, $results[0]['status']);
        $this->assertFalse($results[0]['errors']);
        $approved = $f['registration']->fresh()->status === 'approved';
        $this->assertSame($approved ? 302 : 404, $results[1]['status']);
        $this->assertNull($f['competition']->fresh()->secretariat_id);
        $this->actingAs($f['secretariat'], 'institution')->post($approve['url'], $approve['payload'])->assertNotFound();
    }
}
