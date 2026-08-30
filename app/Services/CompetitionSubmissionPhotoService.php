<?php

namespace App\Services;

use App\Models\CompetitionSubmission;
use App\Models\CompetitionSubmissionPhoto;
use App\Models\Photo;
use App\Support\CompetitionRules\CompetitionEligibilityEvaluator;
use App\Support\Photo\ExifReader;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Intervention\Image\ImageManager;

class CompetitionSubmissionPhotoService
{
    public function __construct(
        private readonly CompetitionEligibilityEvaluator $eligibility,
        private readonly ExifReader $exif,
        private readonly CompetitionPhaseService $phases,
    ) {}

    public function fromPortfolio(CompetitionSubmission $submission, Photo $photo, ?string $captureDeviceId, array $processingMethodIds): CompetitionSubmissionPhoto
    {
        abort_unless($photo->user_id === $submission->entry->user_id, 404);
        $bytes = Storage::disk('public')->get($photo->disk_path);
        $metadata = $photo->only([
            'title', 'location', 'taken_at', 'camera_make', 'camera_model', 'lens', 'focal_length',
            'aperture', 'shutter_speed', 'iso', 'exif_captured_at', 'color_space', 'exif_raw', 'exif_missing',
        ]);

        return $this->store($submission, $bytes, $photo->original_filename, $photo->mime_type, $photo->width, $photo->height, $metadata, $captureDeviceId, $processingMethodIds, $photo->id);
    }

    public function fromUpload(CompetitionSubmission $submission, UploadedFile $file, ?string $captureDeviceId, array $processingMethodIds): CompetitionSubmissionPhoto
    {
        $metadata = $this->exif->read($file->getRealPath());

        return $this->store(
            $submission,
            $file->getContent(),
            $file->getClientOriginalName(),
            $file->getMimeType() ?: 'application/octet-stream',
            $metadata['width'] ?? null,
            $metadata['height'] ?? null,
            $metadata,
            $captureDeviceId,
            $processingMethodIds,
        );
    }

    public function remove(CompetitionSubmissionPhoto $photo): void
    {
        $photo->loadMissing('submission.entry.competition');
        if (! $photo->submission->status->isEditable() || ! $this->phases->acceptsApplications($photo->submission->entry->competition)) {
            throw ValidationException::withMessages(['photo' => __('uye.competitions.errors.submission_locked')]);
        }

        Storage::disk('local')->delete($photo->disk_path);
        if ($photo->jury_path) {
            Storage::disk('local')->delete($photo->jury_path);
            Storage::disk('public')->delete($photo->jury_path);
        }
        $photo->delete();
    }

    private function store(CompetitionSubmission $submission, string $bytes, string $filename, string $mime, ?int $width, ?int $height, array $metadata, ?string $captureDeviceId, array $processingMethodIds, ?string $sourcePhotoId = null): CompetitionSubmissionPhoto
    {
        $submission->loadMissing('category.captureDevices', 'category.processingMethods', 'entry.competition');
        if (! $submission->status->isEditable()) {
            throw ValidationException::withMessages(['photo' => __('uye.competitions.errors.submission_locked')]);
        }
        if (! $this->phases->acceptsApplications($submission->entry->competition)) {
            throw ValidationException::withMessages(['photo' => __('uye.competitions.violations.applications_not_open')]);
        }
        if ($submission->photos()->count() >= $submission->category->max_photos_per_participant) {
            throw ValidationException::withMessages(['photo' => __('uye.competitions.errors.photo_limit')]);
        }

        $check = $this->eligibility->evaluatePhoto($submission->category, [
            'capture_device_id' => $captureDeviceId,
            'processing_method_ids' => $processingMethodIds,
        ]);
        if (! $check['eligible']) {
            throw ValidationException::withMessages(['photo' => collect($check['violations'])->map(fn ($code) => __('uye.competitions.violations.'.$code))->join(' ')]);
        }

        $hash = hash('sha256', $bytes);
        if ($submission->photos()->where('sha256', $hash)->exists()) {
            throw ValidationException::withMessages(['photo' => __('uye.competitions.errors.duplicate_photo')]);
        }

        $id = (string) Str::uuid();
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION)) ?: 'jpg';
        $base = "competition-submissions/{$submission->entry->competition_id}/{$submission->id}/{$id}";
        $privatePath = "{$base}.{$extension}";
        $juryPath = "{$base}-jury.jpg";
        Storage::disk('local')->put($privatePath, $bytes);

        try {
            $image = ImageManager::imagick()->read($bytes);
            $image->scaleDown(width: 2400, height: 2400);
            Storage::disk('local')->put($juryPath, (string) $image->toJpeg(quality: 90, progressive: true));
        } catch (\Throwable) {
            $juryPath = "{$base}-jury.{$extension}";
            Storage::disk('local')->put($juryPath, $bytes);
        }

        return $submission->photos()->create([
            'source_photo_id' => $sourcePhotoId,
            'capture_device_id' => $captureDeviceId,
            'disk_path' => $privatePath,
            'jury_path' => $juryPath,
            'original_filename' => $filename,
            'mime_type' => $mime,
            'file_size_bytes' => strlen($bytes),
            'width' => $width,
            'height' => $height,
            'sha256' => $hash,
            'metadata_snapshot' => $metadata,
            'processing_method_ids' => array_values($processingMethodIds),
            'eligibility_snapshot' => $check,
            'sort_order' => ($submission->photos()->max('sort_order') ?? 0) + 10,
        ]);
    }
}
