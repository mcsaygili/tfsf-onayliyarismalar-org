<?php

namespace Tests\Feature;

use App\Models\Photo;
use App\Services\CompetitionSubmissionPhotoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\Concerns\CreatesSecuritySubmission;
use Tests\TestCase;

class CompetitionPhotoRulesIntegrationTest extends TestCase
{
    use CreatesSecuritySubmission, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Storage::fake('public');
    }

    public function test_portfolio_metadata_cannot_bypass_actual_image_dimensions(): void
    {
        $submission = $this->securitySubmission();
        $submission->category->update(['photo_rules' => ['min_short_edge' => 100000]]);
        $photo = Photo::factory()->create(['user_id' => $submission->entry->user_id, 'width' => 100000, 'height' => 100000]);
        Storage::disk('public')->put($photo->disk_path, file_get_contents(base_path('tests/Fixtures/identity-metadata.jpg')));
        try {
            app(CompetitionSubmissionPhotoService::class)->fromPortfolio($submission, $photo, null, []);
            $this->fail('Stored portfolio dimensions are not authoritative.');
        } catch (ValidationException $exception) {
            $this->assertSame([__('photo_rules.errors.min_short_edge', ['value' => 100000])], $exception->errors()['photo']);
        }
        $this->assertDatabaseCount('competition_submission_photos', 0);
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_upload_records_actual_dimensions_format_and_rule_snapshot(): void
    {
        $submission = $this->securitySubmission();
        $submission->category->update(['photo_rules' => ['formats' => ['jpeg'], 'max_long_edge' => 100000]]);
        $path = base_path('tests/Fixtures/identity-metadata.jpg');
        $photo = app(CompetitionSubmissionPhotoService::class)->fromUpload($submission,
            new UploadedFile($path, 'misleading.png', 'image/png', null, true), null, []);
        $this->assertSame('image/jpeg', $photo->mime_type);
        $this->assertStringEndsWith('.jpg', $photo->disk_path);
        $this->assertSame(getimagesize($path)[0], $photo->width);
        $this->assertSame(['jpeg'], $photo->eligibility_snapshot['technical']['rules']['formats']);
    }

    public function test_changed_rules_are_rechecked_before_restoring_withdrawn_photo(): void
    {
        $submission = $this->securitySubmission();
        $file = new UploadedFile(base_path('tests/Fixtures/identity-metadata.jpg'), 'photo.jpg', 'image/jpeg', null, true);
        $photo = app(CompetitionSubmissionPhotoService::class)->fromUpload($submission, $file, null, []);
        $photo->update(['withdrawn_at' => now()]);
        $submission->category->update(['photo_rules' => ['min_short_edge' => 100000]]);
        try {
            app(CompetitionSubmissionPhotoService::class)->fromUpload($submission, $file, null, []);
            $this->fail('Restoration must obey current category rules.');
        } catch (ValidationException) {
            $this->assertNotNull($photo->fresh()->withdrawn_at);
        }
        $this->assertDatabaseCount('competition_submission_photos', 1);
        $this->assertCount(2, Storage::disk('local')->allFiles());
    }
}
