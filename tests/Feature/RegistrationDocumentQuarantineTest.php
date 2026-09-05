<?php

namespace Tests\Feature;

use App\Models\CompetitionRegistrationDocument;
use App\Models\CompetitionRegistrationEvent;
use App\Services\CompetitionRegistrationService;
use App\Services\RegistrationDocumentScanService;
use App\Support\Documents\PdfDocumentScanner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\Concerns\CreatesCompetitionRegistration;
use Tests\Support\PassingDocumentScanner;
use Tests\TestCase;

class RegistrationDocumentQuarantineTest extends TestCase
{
    use CreatesCompetitionRegistration, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    private function upload(array $f): CompetitionRegistrationDocument
    {
        return app(CompetitionRegistrationService::class)->upload($f['registration'], $f['member'], 1, 1, $this->registrationPdf());
    }

    public function test_unscanned_document_cannot_be_downloaded_submitted_or_used_by_existing_approval(): void
    {
        $f = $this->registrationFixture();
        $document = $this->upload($f);
        $this->assertSame('pending', $document->scan_status);
        $this->actingAs($f['member'])->get(route('competitions.registration.documents.show', $document))->assertNotFound();
        $this->post(route('competitions.registration.submit', $f['registration']), ['version' => 2])->assertSessionHasErrors('registration');
        $f['registration']->update(['status' => 'approved', 'submitted_at' => now()]);
        $this->assertFalse(app(CompetitionRegistrationService::class)->isApproved($f['competition'], $f['member']->id));
        $this->actingAs($f['staff'], 'institution')->get(route('institution.registrations.documents.show', $document))->assertNotFound();
    }

    public function test_clean_verdict_is_bound_to_exact_bytes_and_policy_and_unblocks_submission(): void
    {
        $f = $this->registrationFixture();
        $document = $this->upload($f);
        $this->app->instance(PdfDocumentScanner::class, new PassingDocumentScanner);
        $this->assertSame('clean', app(RegistrationDocumentScanService::class)->scan($document->id));
        $document->refresh();
        $this->assertTrue($document->isTrusted());
        $this->assertSame($document->sha256, $document->scan_sha256);
        $this->assertSame(PdfDocumentScanner::POLICY, $document->scan_policy);
        $this->assertNull($document->scan_token);
        $this->assertSame([], Storage::disk('local')->allFiles('document-scan-work'));
        $this->actingAs($f['member'])->get(route('competitions.registration.documents.show', $document))->assertOk();
        $this->post(route('competitions.registration.submit', $f['registration']), ['version' => 2])->assertSessionHasNoErrors();
        $this->assertSame('skipped', app(RegistrationDocumentScanService::class)->scan($document->id));
        $this->assertSame(1, $document->refresh()->scan_attempts);
        $this->assertSame(1, $f['registration']->events()->where('event', 'document_scan_clean')->count());
    }

    public function test_scanner_failure_stays_quarantined_and_can_be_retried(): void
    {
        $f = $this->registrationFixture();
        $document = $this->upload($f);
        $this->mock(PdfDocumentScanner::class)->shouldReceive('scan')->once()->andThrow(new \RuntimeException('Sensitive tool output must not be recorded'));
        $this->assertSame('error', app(RegistrationDocumentScanService::class)->scan($document->id));
        $this->assertSame('scanner_failed', $document->refresh()->scan_reason);
        $this->assertFalse($document->isTrusted());
        $this->assertStringNotContainsString('Sensitive', $document->toJson());
        $this->app->instance(PdfDocumentScanner::class, new PassingDocumentScanner);
        $this->artisan('tfsf:scan-registration-documents', ['--document' => $document->id])->expectsOutputToContain('clean')->assertSuccessful();
        $this->assertSame(2, $document->refresh()->scan_attempts);
    }

    public function test_rejected_verdict_closes_access_and_is_not_automatically_retried(): void
    {
        $f = $this->registrationFixture();
        $document = $this->upload($f);
        $this->mock(PdfDocumentScanner::class)->shouldReceive('scan')->once()->andReturn(['status' => 'rejected', 'reason' => 'malware_detected', 'engine' => 'test']);
        $this->assertSame('rejected', app(RegistrationDocumentScanService::class)->scan($document->id));
        $this->actingAs($f['member'])->get(route('competitions.registration.documents.show', $document))->assertNotFound();
        $this->artisan('tfsf:scan-registration-documents')->assertSuccessful();
        $this->assertSame(1, $document->refresh()->scan_attempts);
    }

    public function test_interrupted_lease_can_be_reclaimed_but_active_lease_is_not_scanned_twice(): void
    {
        $f = $this->registrationFixture();
        $document = $this->upload($f);
        $document->forceFill(['scan_status' => 'scanning', 'scan_token' => (string) Str::uuid(), 'scan_started_at' => now()])->save();
        $this->app->instance(PdfDocumentScanner::class, new PassingDocumentScanner);
        $this->assertSame('skipped', app(RegistrationDocumentScanService::class)->scan($document->id));
        $this->travel(301)->seconds();
        $this->artisan('tfsf:scan-registration-documents')->expectsOutputToContain('clean')->assertSuccessful();
        $this->assertTrue($document->refresh()->isTrusted());
    }

    public function test_old_worker_cannot_overwrite_newer_lease_result(): void
    {
        $f = $this->registrationFixture();
        $document = $this->upload($f);
        $this->mock(PdfDocumentScanner::class)->shouldReceive('scan')->once()->andReturnUsing(function () use ($document) {
            $document->fresh()->forceFill(['scan_token' => (string) Str::uuid(), 'scan_status' => 'scanning'])->save();

            return ['status' => 'clean', 'reason' => 'verified', 'engine' => 'test'];
        });
        $this->assertSame('superseded', app(RegistrationDocumentScanService::class)->scan($document->id));
        $this->assertSame('scanning', $document->refresh()->scan_status);
        $this->assertFalse($document->isTrusted());
    }

    public function test_changed_bytes_or_missing_file_are_rejected_before_scanner_execution(): void
    {
        $f = $this->registrationFixture();
        $document = $this->upload($f);
        $this->mock(PdfDocumentScanner::class)->shouldNotReceive('scan');
        Storage::disk('local')->put($document->disk_path, 'corrupt');
        $this->assertSame('rejected', app(RegistrationDocumentScanService::class)->scan($document->id));
        $this->assertSame('checksum_mismatch', $document->refresh()->scan_reason);
        Storage::disk('local')->delete($document->disk_path);
        $this->assertSame('rejected', app(RegistrationDocumentScanService::class)->scan($document->id));
        $this->assertSame('file_missing', $document->refresh()->scan_reason);
    }

    public function test_policy_change_invalidates_previously_clean_proof_and_recovery_rescans_it(): void
    {
        $f = $this->registrationFixture();
        $document = $this->upload($f);
        $this->app->instance(PdfDocumentScanner::class, new PassingDocumentScanner);
        app(RegistrationDocumentScanService::class)->scan($document->id);
        $document->refresh()->forceFill(['scan_policy' => 'old-policy'])->save();
        $this->assertFalse($document->isTrusted());
        $this->actingAs($f['member'])->get(route('competitions.registration.documents.show', $document))->assertNotFound();
        $this->artisan('tfsf:scan-registration-documents')->expectsOutputToContain('clean')->assertSuccessful();
    }

    public function test_recovery_repairs_missing_lease_and_invalid_clean_proof(): void
    {
        $f = $this->registrationFixture();
        $document = $this->upload($f);
        $this->app->instance(PdfDocumentScanner::class, new PassingDocumentScanner);
        $document->forceFill(['scan_status' => 'scanning', 'scan_started_at' => null])->save();
        $this->artisan('tfsf:scan-registration-documents')->expectsOutputToContain('clean')->assertSuccessful();
        foreach (['scanned_at' => null, 'scan_sha256' => str_repeat('0', 64)] as $field => $value) {
            $document->refresh()->forceFill([$field => $value])->save();
            $this->assertFalse($document->isTrusted());
            $this->assertSame('pending', $document->scanDisplayStatus());
            $this->artisan('tfsf:scan-registration-documents')->expectsOutputToContain('clean')->assertSuccessful();
        }
        $this->assertSame(3, $document->refresh()->scan_attempts);
        $this->artisan('tfsf:scan-registration-documents', ['--document' => (string) Str::uuid()])->assertFailed();
    }

    public function test_audit_failure_does_not_commit_a_clean_verdict(): void
    {
        $f = $this->registrationFixture();
        $document = $this->upload($f);
        $this->app->instance(PdfDocumentScanner::class, new PassingDocumentScanner);
        CompetitionRegistrationEvent::creating(fn () => throw new \RuntimeException('Audit unavailable'));
        try {
            app(RegistrationDocumentScanService::class)->scan($document->id);
            $this->fail('Expected audit failure');
        } catch (\RuntimeException $e) {
            $this->assertSame('Audit unavailable', $e->getMessage());
        }
        $this->assertFalse($document->refresh()->isTrusted());
        $this->assertSame('scanning', $document->scan_status);
        $this->assertSame([], Storage::disk('local')->allFiles('document-scan-work'));
    }
}
