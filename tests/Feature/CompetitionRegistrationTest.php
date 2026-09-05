<?php

namespace Tests\Feature;

use App\Enums\CompetitionStatus;
use App\Models\Competition;
use App\Models\CompetitionRegistrationDocument;
use App\Models\CompetitionRegistrationEvent;
use App\Models\InstitutionStaff;
use App\Models\Temsilci;
use App\Models\User;
use App\Services\CompetitionEntryService;
use App\Services\CompetitionRegistrationService;
use App\Services\MemberEligibilityService;
use App\Services\RegistrationDocumentScanService;
use App\Support\Documents\PdfDocumentScanner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\Concerns\CreatesCompetitionRegistration;
use Tests\Support\PassingDocumentScanner;
use Tests\TestCase;

class CompetitionRegistrationTest extends TestCase
{
    use CreatesCompetitionRegistration, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->app->instance(PdfDocumentScanner::class, new PassingDocumentScanner);
        CompetitionRegistrationDocument::created(fn ($document) => app(RegistrationDocumentScanService::class)->scan($document->id));
    }

    private function uploaded(array $f)
    {
        return app(CompetitionRegistrationService::class)->upload($f['registration']->fresh(), $f['member'], $f['registration']->fresh()->version, 1, $this->registrationPdf());
    }

    private function pending(array $f): void
    {
        app(CompetitionRegistrationService::class)->submit($f['registration']->fresh(), $f['member'], $f['registration']->fresh()->version);
    }

    public function test_registration_is_competition_specific_unique_and_does_not_change_member_type(): void
    {
        $f = $this->registrationFixture(0);
        $type = $f['member']->uye_turu;
        $same = app(CompetitionRegistrationService::class)->register($f['competition'], $f['member']);
        $this->assertSame($same->id, $f['registration']->id);
        $this->assertSame(1, $same->number);
        $other = User::factory()->create(['date_of_birth' => '1990-01-01', 'gender' => 'male']);
        $next = app(CompetitionRegistrationService::class)->register($f['competition'], $other);
        $this->assertSame(2, $next->number);
        $this->assertSame($type, $f['member']->fresh()->uye_turu);
        $this->assertDatabaseCount('competition_registrations', 2);
        $this->assertDatabaseCount('competition_registration_events', 2);
    }

    public function test_member_upload_submit_and_institution_approval_unlock_photo_entry(): void
    {
        $f = $this->registrationFixture();
        $this->assertFalse(app(MemberEligibilityService::class)->forCompetition($f['competition'], $f['member'])['eligible']);
        $this->actingAs($f['member'])->post(route('competitions.start', $f['competition']))->assertSessionHasErrors();
        $this->post(route('competitions.registration.upload', $f['registration']), ['version' => 1, 'slot' => 1, 'document' => $this->registrationPdf()])->assertSessionHasNoErrors();
        $this->post(route('competitions.registration.submit', $f['registration']), ['version' => 2])->assertSessionHasNoErrors();
        $this->get(route('competitions.registration.show', $f['competition']))->assertOk()->assertSee(__('registration.pending'));
        $this->actingAs($f['staff'], 'institution')->get(route('institution.registrations.index'))->assertOk()->assertSee($f['member']->first_name);
        $this->get(route('institution.registrations.show', $f['registration']))->assertOk();
        $this->post(route('institution.registrations.decide', $f['registration']), ['version' => 3, 'decision' => 'approved'])->assertSessionHasNoErrors();
        $this->assertSame('approved', $f['registration']->fresh()->status);
        $this->assertSame($f['staff']->id, $f['registration']->fresh()->reviewed_by_id);
        $this->assertTrue(app(MemberEligibilityService::class)->forCompetition($f['competition'], $f['member'])['eligible']);
        $this->assertSame($f['member']->id, app(CompetitionEntryService::class)->entryFor($f['competition'], $f['member'])->user_id);
    }

    public function test_required_documents_and_current_registration_version_are_enforced(): void
    {
        $f = $this->registrationFixture(2);
        $this->actingAs($f['member'])->post(route('competitions.registration.submit', $f['registration']), ['version' => 1])->assertSessionHasErrors('registration');
        $this->uploaded($f);
        $this->post(route('competitions.registration.submit', $f['registration']), ['version' => 2])->assertSessionHasErrors('registration');
        $this->post(route('competitions.registration.upload', $f['registration']), ['version' => 1, 'slot' => 2, 'document' => $this->registrationPdf('second')])->assertSessionHasErrors('registration');
        $this->assertDatabaseCount('competition_registration_documents', 1);
        $this->post(route('competitions.registration.upload', $f['registration']), ['version' => 2, 'slot' => 2, 'document' => $this->registrationPdf('second')])->assertSessionHasNoErrors();
        $this->post(route('competitions.registration.submit', $f['registration']), ['version' => 3])->assertSessionHasNoErrors();
        $this->post(route('competitions.registration.upload', $f['registration']), ['version' => 4, 'slot' => 3, 'document' => $this->registrationPdf('third')])->assertSessionHasErrors('registration');
    }

    public function test_documents_are_private_downloads_and_drafts_are_not_visible_to_reviewers(): void
    {
        $f = $this->registrationFixture();
        $document = $this->uploaded($f);
        $url = route('competitions.registration.documents.show', $document);
        $this->actingAs($f['member'])->get($url)->assertOk()->assertHeader('Content-Type', 'application/octet-stream')->assertHeader('Cache-Control', 'no-store, private')->assertHeader('X-Content-Type-Options', 'nosniff')->assertHeader('Content-Security-Policy', 'sandbox')->assertStreamedContent(Storage::disk('local')->get($document->disk_path));
        $this->actingAs(User::factory()->create())->get($url)->assertNotFound();
        $staffUrl = route('institution.registrations.documents.show', $document);
        $this->actingAs($f['staff'], 'institution')->get($staffUrl)->assertNotFound();
        $this->pending($f);
        $this->get($staffUrl)->assertOk();
        $this->actingAs(InstitutionStaff::factory()->create(), 'institution')->get($staffUrl)->assertNotFound();
    }

    public function test_representative_review_requires_current_competition_assignment(): void
    {
        $f = $this->registrationFixture(0);
        $representative = Temsilci::factory()->create();
        $f['competition']->forceFill(['representative_id' => $representative->id])->save();
        $f['registration']->update(['reviewer' => 'representative']);
        $this->pending($f);
        $this->actingAs($f['staff'], 'institution')->get(route('institution.registrations.show', $f['registration']))->assertNotFound();
        $this->actingAs($representative, 'temsilci')->get(route('temsilci.registrations.show', $f['registration']))->assertOk();
        $f['competition']->forceFill(['representative_id' => null])->save();
        $this->post(route('temsilci.registrations.decide', $f['registration']), ['version' => 2, 'decision' => 'approved'])->assertNotFound();
    }

    public function test_changes_preserve_document_versions_and_stale_reviewer_cannot_approve_new_documents(): void
    {
        $f = $this->registrationFixture();
        $first = $this->uploaded($f);
        $this->pending($f);
        $this->actingAs($f['staff'], 'institution')->post(route('institution.registrations.decide', $f['registration']), ['version' => 3, 'decision' => 'changes_requested', 'note' => 'Upload the complete certificate.'])->assertSessionHasNoErrors();
        $service = app(CompetitionRegistrationService::class);
        $second = $service->upload($f['registration']->fresh(), $f['member'], 4, 1, $this->registrationPdf('Corrected certificate'));
        $this->assertSame(2, $second->version);
        $this->assertFalse($first->fresh()->is_current);
        Storage::disk('local')->assertExists([$first->disk_path, $second->disk_path]);
        $this->pending($f);
        $this->post(route('institution.registrations.decide', $f['registration']), ['version' => 3, 'decision' => 'approved'])->assertSessionHasErrors('registration');
        $this->assertSame('pending', $f['registration']->fresh()->status);
        $this->post(route('institution.registrations.decide', $f['registration']), ['version' => 6, 'decision' => 'approved'])->assertSessionHasNoErrors();
        $this->assertSame([$second->id], $f['registration']->events()->where('event', 'approved')->sole()->context['documents']);
    }

    public function test_forged_paths_non_pdf_duplicate_and_excess_slots_are_rejected(): void
    {
        $f = $this->registrationFixture();
        $this->actingAs($f['member']);
        foreach ([['version' => 1, 'slot' => 1, 'document' => '../other.pdf'], ['version' => 1, 'slot' => 1, 'document' => UploadedFile::fake()->createWithContent('fake.pdf', '<script>alert(1)</script>')], ['version' => 1, 'slot' => 4, 'document' => $this->registrationPdf()]] as $input) {
            $this->post(route('competitions.registration.upload', $f['registration']), $input)->assertSessionHasErrors();
        }
        $this->assertDatabaseCount('competition_registration_documents', 0);
        $this->uploaded($f);
        $this->post(route('competitions.registration.upload', $f['registration']), ['version' => 2, 'slot' => 2, 'document' => $this->registrationPdf()])->assertSessionHasErrors('registration');
        $this->assertDatabaseCount('competition_registration_documents', 1);
    }

    public function test_missing_or_corrupt_document_cannot_be_downloaded_or_approved(): void
    {
        $f = $this->registrationFixture();
        $document = $this->uploaded($f);
        $this->pending($f);
        Storage::disk('local')->put($document->disk_path, 'changed');
        $this->actingAs($f['member'])->get(route('competitions.registration.documents.show', $document))->assertNotFound();
        $this->actingAs($f['staff'], 'institution')->post(route('institution.registrations.decide', $f['registration']), ['version' => 3, 'decision' => 'approved'])->assertSessionHasErrors('registration');
        $this->assertSame('pending', $f['registration']->fresh()->status);
    }

    public function test_failed_audit_rolls_back_new_file_and_document_version(): void
    {
        $f = $this->registrationFixture();
        $first = $this->uploaded($f);
        CompetitionRegistrationEvent::creating(fn () => throw new \RuntimeException('Injected audit failure'));
        try {
            app(CompetitionRegistrationService::class)->upload($f['registration']->fresh(), $f['member'], 2, 1, $this->registrationPdf('new'));
            $this->fail('Expected audit failure');
        } catch (\RuntimeException $e) {
            $this->assertSame('Injected audit failure', $e->getMessage());
        }
        $this->assertSame(2, $f['registration']->fresh()->version);
        $this->assertTrue($first->fresh()->is_current);
        $this->assertSame([$first->disk_path], Storage::disk('local')->allFiles('registration-documents'));
        $this->assertDatabaseCount('competition_registration_documents', 1);
    }

    public function test_revocation_blocks_existing_draft_photo_writes_but_used_approval_cannot_be_revoked_silently(): void
    {
        $f = $this->registrationFixture(0);
        $this->pending($f);
        $service = app(CompetitionRegistrationService::class);
        $service->decide($f['registration'], $f['staff'], 2, 'approved', null);
        $service->decide($f['registration'], $f['staff'], 3, 'changes_requested', 'Review eligibility again.');
        $this->actingAs($f['member'])->post(route('competitions.submission.upload', $f['submission']), ['photo' => new UploadedFile(base_path('tests/Fixtures/identity-metadata.jpg'), 'photo.jpg', 'image/jpeg', null, true)])->assertSessionHasErrors();
        $this->assertDatabaseCount('competition_submission_photos', 0);
        $this->pending($f);
        $service->decide($f['registration'], $f['staff'], 5, 'approved', null);
        $f['submission']->entry->update(['submitted_at' => now()]);
        $this->actingAs($f['staff'], 'institution')->post(route('institution.registrations.decide', $f['registration']), ['version' => 6, 'decision' => 'changes_requested', 'note' => 'Revoke used approval'])->assertSessionHasErrors('registration');
        $this->assertSame('approved', $f['registration']->fresh()->status);
    }

    public function test_rejection_requires_reason_and_preserves_history_without_reopening(): void
    {
        $f = $this->registrationFixture(0);
        $this->pending($f);
        $this->actingAs($f['staff'], 'institution')->post(route('institution.registrations.decide', $f['registration']), ['version' => 2, 'decision' => 'rejected'])->assertSessionHasErrors('registration');
        $this->post(route('institution.registrations.decide', $f['registration']), ['version' => 2, 'decision' => 'rejected', 'note' => 'Not eligible for this event.'])->assertSessionHasNoErrors();
        $this->actingAs($f['member'])->post(route('competitions.registration.submit', $f['registration']), ['version' => 3])->assertSessionHasErrors('registration');
        $this->assertSame('rejected', $f['registration']->fresh()->status);
    }

    public function test_closed_competition_blocks_registration_and_edits(): void
    {
        $f = $this->registrationFixture(0);
        $f['competition']->update(['application_ends_at' => now()->subMinute()]);
        $this->actingAs($f['member'])->post(route('competitions.registration.store', $f['competition']))->assertSessionHasErrors('registration');
        $this->post(route('competitions.registration.submit', $f['registration']), ['version' => 1])->assertSessionHasErrors('registration');
        $this->assertSame('draft', $f['registration']->fresh()->status);
    }

    public function test_registration_settings_are_validated_and_frozen_after_first_registration(): void
    {
        $staff = InstitutionStaff::factory()->create();
        $competition = Competition::factory()->create(['institution_id' => $staff->institution_id, 'status' => CompetitionStatus::Draft, 'current_step' => 4]);
        $this->actingAs($staff, 'institution')->put(route('institution.competitions.step.update', [$competition, 4]), ['action' => 'draft', 'registration_required' => 1, 'registration_document_min' => 4, 'registration_reviewer' => 'institution'])->assertSessionHasErrors('registration_document_min');
        $this->put(route('institution.competitions.step.update', [$competition, 4]), ['action' => 'draft', 'registration_required' => 1, 'registration_document_min' => 2, 'registration_reviewer' => 'representative'])->assertSessionHasNoErrors();
        $this->assertTrue($competition->fresh()->registration_required);
        $this->assertSame(2, $competition->fresh()->registration_document_min);
        $this->assertSame('representative', $competition->fresh()->registration_reviewer);
        $f = $this->registrationFixture(0);
        $f['competition']->forceFill(['status' => CompetitionStatus::Draft])->save();
        $this->expectException(ValidationException::class);
        app(CompetitionRegistrationService::class)->configure($f['competition'], ['registration_required' => false, 'registration_document_min' => 0, 'registration_reviewer' => 'institution']);
    }

    public function test_registration_cannot_be_created_for_a_future_publication(): void
    {
        $f = $this->registrationFixture(0);
        $f['competition']->forceFill(['published_at' => now()->addDay()])->save();
        $this->actingAs($f['member'])->post(route('competitions.registration.store', $f['competition']))->assertSessionHasErrors('registration');
        $this->post(route('competitions.registration.submit', $f['registration']), ['version' => 1])->assertSessionHasErrors('registration');
    }

    public function test_member_cannot_modify_another_registration_or_upload_oversized_document(): void
    {
        $f = $this->registrationFixture();
        $url = route('competitions.registration.upload', $f['registration']);
        $this->actingAs(User::factory()->create())->post($url, ['version' => 1, 'slot' => 1, 'document' => $this->registrationPdf()])->assertNotFound();
        $this->actingAs($f['member'])->post($url, ['version' => 1, 'slot' => 1, 'document' => $this->registrationPdf()->size(10241)])->assertSessionHasErrors('document');
        $this->assertDatabaseCount('competition_registration_documents', 0);
    }

    public function test_removing_a_document_preserves_audit_and_next_upload_uses_new_version(): void
    {
        $f = $this->registrationFixture();
        $first = $this->uploaded($f);
        $this->actingAs($f['member'])->delete(route('competitions.registration.documents.destroy', $first), ['version' => 2])->assertSessionHasNoErrors();
        $this->assertFalse($first->fresh()->is_current);
        $this->post(route('competitions.registration.submit', $f['registration']), ['version' => 3])->assertSessionHasErrors('registration');
        $second = app(CompetitionRegistrationService::class)->upload($f['registration']->fresh(), $f['member'], 3, 1, $this->registrationPdf('replacement'));
        $this->assertSame(2, $second->version);
        $this->assertSame(1, $f['registration']->events()->where('event', 'document_removed')->count());
        Storage::disk('local')->assertExists($first->disk_path);
    }
}
