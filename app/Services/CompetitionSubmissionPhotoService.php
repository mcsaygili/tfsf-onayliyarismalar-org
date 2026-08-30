<?php

namespace App\Services;

use App\Models\CompetitionSubmission;
use App\Models\CompetitionSubmissionPhoto;
use App\Models\JuryEvaluationSubmission;
use App\Models\JuryScore;
use App\Models\Photo;
use App\Notifications\Juri\EvaluationReopenedNotification;
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
        private readonly CompetitionEntryMutationPolicy $mutations,
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
        if (! $this->mutations->allowsPhoto($photo)) {
            throw ValidationException::withMessages(['photo' => __('uye.competitions.errors.submission_locked')]);
        }

        if (! $photo->submission->status->isEditable()) {
            $photo->update(['withdrawn_at' => now()]);
            $this->reopenEvaluations($photo->submission);
            $photo->submission->entry->events()->create([
                'event' => 'photo_withdrawn_during_evaluation',
                'actor_type' => $photo->submission->entry->user::class,
                'actor_id' => $photo->submission->entry->user_id,
                'context' => ['submission_photo_id' => $photo->id, 'competition_category_id' => $photo->submission->competition_category_id],
            ]);

            return;
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
        if (! $this->mutations->allows($submission)) {
            throw ValidationException::withMessages(['photo' => __('uye.competitions.errors.submission_locked')]);
        }
        if ($submission->activePhotos()->count() >= $submission->category->max_photos_per_participant) {
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
        $withdrawn = $submission->photos()->where('sha256', $hash)->whereNotNull('withdrawn_at')->first();
        if ($withdrawn) {
            $withdrawn->update(['withdrawn_at' => null, 'withdrawal_reason' => null]);
            $this->reopenEvaluations($submission);
            $this->recordRevision($submission, $withdrawn, 'photo_restored_during_evaluation');

            return $withdrawn;
        }
        if ($submission->activePhotos()->where('sha256', $hash)->exists()) {
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

        $stored = $submission->photos()->create([
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

        if (! $submission->status->isEditable()) {
            $this->reopenEvaluations($submission);
            $this->recordRevision($submission, $stored, 'photo_added_during_evaluation');
        }

        return $stored;
    }

    private function recordRevision(CompetitionSubmission $submission, CompetitionSubmissionPhoto $photo, string $event): void
    {
        $submission->entry->events()->create([
            'event' => $event,
            'actor_type' => $submission->entry->user::class,
            'actor_id' => $submission->entry->user_id,
            'context' => ['submission_photo_id' => $photo->id, 'competition_category_id' => $submission->competition_category_id],
        ]);
    }

    private function reopenEvaluations(CompetitionSubmission $submission): void
    {
        $round = $submission->entry->competition->evaluationRounds()->where('round_number', 1)->first();
        if (! $round) {
            return;
        }

        $assignmentIds = $submission->category->jurorAssignments()->pluck('id');
        JuryEvaluationSubmission::query()
            ->where('competition_evaluation_round_id', $round->id)
            ->whereIn('juror_assignment_id', $assignmentIds)
            ->delete();
        JuryScore::query()
            ->where('competition_evaluation_round_id', $round->id)
            ->whereIn('juror_assignment_id', $assignmentIds)
            ->update(['submitted_at' => null]);

        $submission->loadMissing('category.jurorAssignments.juror', 'category.translations', 'entry.competition.translations');
        $submission->category->jurorAssignments->pluck('juror')->filter()->unique('id')
            ->each(fn ($juror) => $juror->notify(new EvaluationReopenedNotification($submission)));
    }
}
