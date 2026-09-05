<?php

namespace Tests\Feature;

use App\Services\CompetitionRegistrationService;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Group;
use Tests\Concerns\CreatesCompetitionRegistration;
use Tests\Concerns\RunsConcurrentAccountRequests;
use Tests\TestCase;

#[Group('mysql-concurrency')]
class RegistrationDocumentScanConcurrencyTest extends TestCase
{
    use CreatesCompetitionRegistration, DatabaseMigrations, RunsConcurrentAccountRequests;

    protected function beforeRefreshingDatabase(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            $this->markTestSkipped('Scan lease concurrency requires MariaDB.');
        }
        $this->assertSame('tfsf_testing', DB::connection()->getDatabaseName());
        $this->assertTrue(app()->environment('testing'));
    }

    public function test_two_workers_scan_a_document_once_and_publish_one_verdict(): void
    {
        $root = sys_get_temp_dir().'/tfsf-entry-'.Str::uuid();
        config(['filesystems.disks.local.root' => $root.'/local']);
        Storage::forgetDisk('local');
        try {
            $f = $this->registrationFixture();
            $document = app(CompetitionRegistrationService::class)->upload($f['registration'], $f['member'], 1, 1, $this->registrationPdf());
            $this->assertSame(1, DB::table('jobs')->where('queue', 'document-scans')->count());
            $results = $this->simultaneousRequests(['scan_document_id' => $document->id, 'storage_root' => $root]);
            $outcomes = array_column($results, 'scan_result');
            sort($outcomes);
            $this->assertSame(['clean', 'skipped'], $outcomes);
            $this->assertSame(1, $document->refresh()->scan_attempts);
            $this->assertTrue($document->isTrusted());
            $this->assertSame(1, $f['registration']->events()->where('event', 'document_scan_clean')->count());
        } finally {
            File::deleteDirectory($root);
        }
    }
}
