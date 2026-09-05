<?php

namespace Tests\Feature;

use App\Services\CompetitionRegistrationService;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\CreatesRegistrationException;
use Tests\TestCase;

class RegistrationExceptionMigrationTest extends TestCase
{
    use CreatesRegistrationException, DatabaseMigrations;

    protected function beforeRefreshingDatabase(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            $this->markTestSkipped('Migration rollback with data requires MariaDB.');
        }
        $this->assertSame('tfsf_testing', DB::connection()->getDatabaseName());
        $this->assertTrue(app()->environment('testing'));
    }

    public function test_upgrade_defaults_to_no_waiver_and_rollback_preserves_reason_events_and_document_minimum(): void
    {
        $f = $this->exceptionFixture();
        $this->assertFalse($f['registration']->fresh()->documents_waived);
        $service = app(CompetitionRegistrationService::class);
        $service->approveDirectly($f['competition'], $f['staff'], $f['member'], 1, 1, true, 'Synthetic attendance confirmed.');
        $events = DB::table('competition_registration_events')->orderBy('id')->get()->toJson();
        $migration = require database_path('migrations/2026_09_05_270000_authorize_registration_exceptions.php');
        $migration->down();
        $row = DB::table('competition_registrations')->first();
        $this->assertSame(1, $row->document_min);
        $this->assertSame('Synthetic attendance confirmed.', $row->review_note);
        $migration->up();
        $this->assertFalse($f['registration']->fresh()->documents_waived);
        $this->assertFalse($service->isApproved($f['competition'], $f['member']->id));
        $this->assertDatabaseCount('registration_exception_grants', 0);
        $this->assertSame($events, DB::table('competition_registration_events')->orderBy('id')->get()->toJson());
    }
}
