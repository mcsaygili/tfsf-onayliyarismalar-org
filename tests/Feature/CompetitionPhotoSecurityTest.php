<?php

namespace Tests\Feature;

use App\Models\AwardReference;
use App\Models\CompetitionSubmission;
use App\Models\CompetitionSubmissionPhoto;
use App\Models\EysUser;
use App\Models\Juri;
use App\Services\CompetitionSubmissionPhotoService;
use App\Services\JuryPhotoRenderer;
use App\Services\ResultPublicationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Symfony\Component\Process\Process;
use Tests\Concerns\CreatesSecuritySubmission;
use Tests\TestCase;

class CompetitionPhotoSecurityTest extends TestCase
{
    use CreatesSecuritySubmission, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Storage::fake('public');
    }

    private function upload(CompetitionSubmission $submission): CompetitionSubmissionPhoto
    {
        return app(CompetitionSubmissionPhotoService::class)->fromUpload($submission,
            new UploadedFile(base_path('tests/Fixtures/identity-metadata.jpg'), 'test.jpg', 'image/jpeg', null, true), null, []);
    }

    public function test_jury_copy_strips_identity_and_gps_while_preserving_original(): void
    {
        $photo = $this->upload($this->securitySubmission());
        $disk = Storage::disk('local');
        $original = json_decode((new Process(['exiftool', '-j', $disk->path($photo->disk_path)]))->mustRun()->getOutput(), true, flags: JSON_THROW_ON_ERROR)[0];
        $safe = json_decode((new Process(['exiftool', '-j', $disk->path($photo->jury_path)]))->mustRun()->getOutput(), true, flags: JSON_THROW_ON_ERROR)[0];
        $this->assertSame('TFSF_AUDIT_PHOTOGRAPHER', $original['Artist']);
        $this->assertArrayHasKey('GPSLatitude', $original);
        $this->assertArrayNotHasKey('Artist', $safe);
        $this->assertArrayNotHasKey('GPSLatitude', $safe);
        $this->assertNotNull($photo->jury_sanitized_at);
        $this->assertSame(hash_file('sha256', base_path('tests/Fixtures/identity-metadata.jpg')), hash_file('sha256', $disk->path($photo->disk_path)));
    }

    public function test_failed_renderer_does_not_store_original_as_jury_copy(): void
    {
        $submission = $this->securitySubmission();
        $this->mock(JuryPhotoRenderer::class)->shouldReceive('render')->once()->andThrow(new \RuntimeException('Renderer failed'));
        try {
            $this->upload($submission);
            $this->fail('A failed derivative must reject the upload.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('photo', $exception->errors());
        }
        $this->assertDatabaseCount('competition_submission_photos', 0);
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_jury_cannot_access_unverified_or_missing_copy(): void
    {
        $submission = $this->securitySubmission();
        $photo = $this->upload($submission);
        $juror = Juri::factory()->create();
        $submission->category->jurorAssignments()->create(['juror_id' => $juror->id, 'sort_order' => 10]);
        $this->actingAs($juror, 'juri')->get(route('juri.evaluations.photos.show', $photo))->assertOk();
        $photo->update(['jury_sanitized_at' => null]);
        $this->get(route('juri.evaluations.photos.show', $photo))->assertNotFound();
        $photo->update(['jury_sanitized_at' => now(), 'jury_path' => null]);
        $this->get(route('juri.evaluations.photos.show', $photo))->assertNotFound();
    }

    public function test_public_result_cannot_fall_back_to_original(): void
    {
        $submission = $this->securitySubmission();
        $photo = $this->upload($submission);
        $competition = $submission->entry->competition;
        $competition->forceFill(['results_published_at' => now()])->save();
        $round = $competition->evaluationRounds()->create(['name' => 'Final', 'round_number' => 1, 'status' => 'finalized', 'is_final' => true]);
        $result = $round->results()->create(['submission_photo_id' => $photo->id, 'total_score' => 9, 'average_score' => 9, 'score_count' => 1, 'rank' => 1]);
        $award = $submission->category->awards()->create(['award_reference_id' => AwardReference::create(['code' => 'security-award', 'status' => true])->id, 'quantity' => 1, 'sort_order' => 10]);
        $result->awards()->create(['competition_category_award_id' => $award->id, 'slot_number' => 1]);
        $submission->update(['status' => 'approved']);
        $publication = app(ResultPublicationService::class)->create($competition, $round, EysUser::factory()->create());
        $competition->forceFill(['results_publication_version' => $publication->version])->save();
        $this->get(route('result.photos.show', $photo))->assertOk();
        $asset = $publication->assets()->sole();
        Storage::disk('local')->delete($asset->disk_path);
        $this->get(route('result.photos.show', $photo))->assertNotFound();
        Storage::disk('local')->put($asset->disk_path, Storage::disk('local')->get($photo->disk_path));
        $this->get(route('result.photos.show', $photo))->assertNotFound();
    }

    public function test_backfill_is_repeatable_and_does_not_modify_original(): void
    {
        $photo = $this->upload($this->securitySubmission());
        $originalHash = hash_file('sha256', Storage::disk('local')->path($photo->disk_path));
        $photo->update(['jury_sanitized_at' => null]);
        $this->artisan('photos:sanitize-competition-copies --dry-run')->expectsOutput('Copies requiring sanitization: 1')->assertSuccessful();
        $this->assertNull($photo->fresh()->jury_sanitized_at);
        $this->artisan('photos:sanitize-competition-copies')->assertSuccessful();
        $this->assertNotNull($photo->fresh()->jury_sanitized_at);
        $path = $photo->fresh()->jury_path;
        $this->artisan('photos:sanitize-competition-copies')->expectsOutput('Sanitized: 0; failed: 0')->assertSuccessful();
        $this->assertSame($path, $photo->fresh()->jury_path);
        $this->assertSame($originalHash, hash_file('sha256', Storage::disk('local')->path($photo->disk_path)));
    }

    public function test_backfill_failure_leaves_copy_unverified(): void
    {
        $photo = $this->upload($this->securitySubmission());
        $photo->update(['jury_sanitized_at' => null]);
        Storage::disk('local')->delete($photo->disk_path);
        $this->artisan('photos:sanitize-competition-copies')->assertFailed();
        $this->assertNull($photo->fresh()->jury_sanitized_at);
    }
}
