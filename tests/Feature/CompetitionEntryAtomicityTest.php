<?php

namespace Tests\Feature;

use App\Models\CompetitionEntryEvent;
use App\Models\CompetitionSubmission;
use App\Models\Photo;
use App\Services\CompetitionSubmissionPhotoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\Concerns\CreatesSecuritySubmission;
use Tests\TestCase;

class CompetitionEntryAtomicityTest extends TestCase
{
    use CreatesSecuritySubmission, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Storage::fake('public');
    }

    public function test_outer_rollback_removes_both_uploaded_copies(): void
    {
        $submission = $this->securitySubmission();
        DB::beginTransaction();
        $photo = $this->add($submission);
        Storage::disk('local')->assertExists([$photo->disk_path, $photo->jury_path]);
        DB::rollBack();
        $this->assertDatabaseCount('competition_submission_photos', 0);
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_rollback_of_delete_preserves_record_and_files_until_commit(): void
    {
        $photo = $this->add($this->securitySubmission());
        DB::beginTransaction();
        app(CompetitionSubmissionPhotoService::class)->remove($photo);
        $this->assertDatabaseCount('competition_submission_photos', 0);
        Storage::disk('local')->assertExists([$photo->disk_path, $photo->jury_path]);
        DB::rollBack();
        $this->assertModelExists($photo);
        Storage::disk('local')->assertExists([$photo->disk_path, $photo->jury_path]);
        app(CompetitionSubmissionPhotoService::class)->remove($photo->fresh());
        Storage::disk('local')->assertMissing([$photo->disk_path, $photo->jury_path]);
    }

    public function test_stale_draft_submission_cannot_bypass_current_locked_state(): void
    {
        $submission = $this->securitySubmission();
        $submission->load('entry.competition', 'category');
        $submission->fresh()->update(['status' => 'pending_approval']);
        try {
            $this->add($submission);
            $this->fail('The current submission state must be checked under the lock.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('photo', $exception->errors());
        }
        $this->assertDatabaseCount('competition_submission_photos', 0);
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_stale_category_quota_is_not_used(): void
    {
        $submission = $this->securitySubmission();
        $submission->load('category');
        $this->add($submission, 'first');
        $submission->category->fresh()->update(['max_photos_per_participant' => 1]);
        try {
            $this->add($submission, 'second');
            $this->fail('A cached quota must not authorize another photo.');
        } catch (ValidationException $exception) {
            $this->assertSame([__('uye.competitions.errors.photo_limit')], $exception->errors()['photo']);
        }
        $this->assertDatabaseCount('competition_submission_photos', 1);
        $this->assertCount(2, Storage::disk('local')->allFiles());
    }

    public function test_revision_event_failure_rolls_back_photo_and_storage(): void
    {
        $submission = $this->securitySubmission();
        $this->evaluationOpen($submission);
        Event::listen('eloquent.creating: '.CompetitionEntryEvent::class, fn () => throw new \RuntimeException('Injected revision failure'));
        try {
            $this->add($submission);
            $this->fail('An incomplete revision must not be accepted.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Injected revision failure', $exception->getMessage());
        }
        $this->assertDatabaseCount('competition_submission_photos', 0);
        $this->assertDatabaseCount('competition_entry_events', 0);
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_repeated_withdrawal_does_not_duplicate_revision_events_or_delete_bytes(): void
    {
        $submission = $this->securitySubmission();
        $photo = $this->add($submission);
        $this->evaluationOpen($submission);
        app(CompetitionSubmissionPhotoService::class)->remove($photo);
        app(CompetitionSubmissionPhotoService::class)->remove($photo);
        $this->assertNotNull($photo->fresh()->withdrawn_at);
        $this->assertDatabaseCount('competition_entry_events', 1);
        Storage::disk('local')->assertExists([$photo->disk_path, $photo->jury_path]);
    }

    private function evaluationOpen(CompetitionSubmission $submission): void
    {
        $submission->entry->competition->update(['application_ends_at' => now()->subDay(), 'evaluation_starts_at' => now()->subHour(),
            'evaluation_ends_at' => now()->addDay(), 'competition_ends_at' => now()->addDay()]);
        $submission->update(['status' => 'approved']);
    }

    private function add(CompetitionSubmission $submission, string $suffix = '')
    {
        $source = Photo::factory()->create(['user_id' => $submission->entry->user_id]);
        Storage::disk('public')->put($source->disk_path, file_get_contents(base_path('tests/Fixtures/identity-metadata.jpg')).$suffix);

        return app(CompetitionSubmissionPhotoService::class)->fromPortfolio($submission, $source, null, []);
    }
}
