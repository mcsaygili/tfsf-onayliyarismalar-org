<?php

namespace App\Services;

use App\Models\CompetitionCategoryJurorAssignment;
use App\Models\CompetitionEntry;
use App\Models\CompetitionSubmission;
use App\Models\CompetitionSubmissionPhoto;
use App\Models\JuryEvaluationSubmission;
use App\Models\JuryScore;
use App\Models\Photo;
use App\Notifications\Juri\EvaluationReopenedNotification;
use App\Support\CompetitionRules\CompetitionEligibilityEvaluator;
use App\Support\Photo\ExifReader;
use App\Support\Photo\SubmissionDeclarations;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CompetitionSubmissionPhotoService
{
    public function __construct(
        private readonly CompetitionEligibilityEvaluator $eligibility,
        private readonly ExifReader $exif,
        private readonly CompetitionEntryMutationPolicy $mutations,
        private readonly JuryPhotoRenderer $juryPhotos,
        private readonly CompetitionPhotoTechnicalValidator $technicalPhotos,
    ) {}

    public function fromPortfolio(CompetitionSubmission $submission, Photo $photo, ?string $captureDeviceId, array $processingMethodIds, ?array $declaration = null): CompetitionSubmissionPhoto
    {
        abort_unless($photo->user_id === $submission->entry->user_id, 404);
        $bytes = Storage::disk('public')->get($photo->disk_path);
        $metadata = $photo->only([
            'title', 'location', 'taken_at', 'camera_make', 'camera_model', 'lens', 'focal_length',
            'aperture', 'shutter_speed', 'iso', 'exif_captured_at', 'color_space', 'exif_raw', 'exif_missing',
        ]);

        return $this->store($submission, $bytes, $photo->original_filename, $photo->mime_type, $photo->width, $photo->height, $metadata, $captureDeviceId, $processingMethodIds, $photo->id, $declaration);
    }

    public function fromUpload(CompetitionSubmission $submission, UploadedFile $file, ?string $captureDeviceId, array $processingMethodIds, ?array $declaration = null): CompetitionSubmissionPhoto
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
            null,
            $declaration,
        );
    }

    public function remove(CompetitionSubmissionPhoto $photo): void
    {
        $competitionId = $photo->submission->entry->competition_id;
        DB::transaction(function () use ($photo, $competitionId) {
            CompetitionMutationLock::acquire($competitionId);
            $submission = $this->lockSubmission($photo->submission);
            $photo = $submission->photos()->whereKey($photo->id)->lockForUpdate()->firstOrFail();
            $photo->setRelation('submission', $submission);
            if (! $this->mutations->allowsPhoto($photo)) {
                throw ValidationException::withMessages(['photo' => __('uye.competitions.errors.submission_locked')]);
            }

            if (! $photo->submission->status->isEditable()) {
                if ($photo->withdrawn_at) {
                    return;
                }
                $photo->update(['withdrawn_at' => now()]);
                $submission->increment('details_version');
                $this->reopenEvaluations($photo->submission);
                $photo->submission->entry->events()->create([
                    'event' => 'photo_withdrawn_during_evaluation',
                    'actor_type' => $photo->submission->entry->user::class,
                    'actor_id' => $photo->submission->entry->user_id,
                    'context' => ['submission_photo_id' => $photo->id, 'competition_category_id' => $photo->submission->competition_category_id],
                ]);

                return;
            }

            $paths = array_filter([$photo->disk_path, $photo->jury_path]);
            $publicPath = $photo->jury_path;
            $photo->delete();
            $submission->increment('details_version');
            DB::afterCommit(function () use ($paths, $publicPath) {
                Storage::disk('local')->delete($paths);
                if ($publicPath) {
                    Storage::disk('public')->delete($publicPath);
                }
            });
        });
    }

    private function lockSubmission(CompetitionSubmission $submission): CompetitionSubmission
    {
        // All member mutations share the entry lock used by submit(). Lock before
        // loading state so waiting writers cannot reuse an old editable snapshot.
        $entry = CompetitionEntry::whereKey($submission->competition_entry_id)->lockForUpdate()->firstOrFail();
        $current = $entry->submissions()->whereKey($submission->id)->lockForUpdate()->firstOrFail();
        $current->setRelation('entry', $entry);

        return $current;
    }

    private function store(CompetitionSubmission $submission, string $bytes, string $filename, string $mime, ?int $width, ?int $height, array $metadata, ?string $captureDeviceId, array $processingMethodIds, ?string $sourcePhotoId = null, ?array $declaration = null): CompetitionSubmissionPhoto
    {
        $competitionId = $submission->entry->competition_id;

        return DB::transaction(function () use ($competitionId, $submission, $bytes, $filename, $mime, $width, $height, $metadata, $captureDeviceId, $processingMethodIds, $sourcePhotoId, $declaration) {
            CompetitionMutationLock::acquire($competitionId);

            return $this->storeLocked($this->lockSubmission($submission), $bytes, $filename, $mime, $width, $height, $metadata, $captureDeviceId, $processingMethodIds, $sourcePhotoId, $declaration);
        });
    }

    private function storeLocked(CompetitionSubmission $submission, string $bytes, string $filename, string $mime, ?int $width, ?int $height, array $metadata, ?string $captureDeviceId, array $processingMethodIds, ?string $sourcePhotoId = null, ?array $declaration = null): CompetitionSubmissionPhoto
    {
        $submission->loadMissing('category.captureDevices', 'category.processingMethods', 'entry.competition');
        if (! $this->mutations->allows($submission)) {
            throw ValidationException::withMessages(['photo' => __('uye.competitions.errors.submission_locked')]);
        }
        if ($submission->activePhotos()->lockForUpdate()->get(['id'])->count() >= $submission->category->max_photos_per_participant) {
            throw ValidationException::withMessages(['photo' => __('uye.competitions.errors.photo_limit')]);
        }

        $check = $this->eligibility->evaluatePhoto($submission->category, [
            'capture_device_id' => $captureDeviceId,
            'processing_method_ids' => $processingMethodIds,
        ]);
        if (! $check['eligible']) {
            throw ValidationException::withMessages(['photo' => collect($check['violations'])->map(fn ($code) => __('uye.competitions.violations.'.$code))->join(' ')]);
        }

        $technical = $this->technicalPhotos->validate($submission->category, $bytes);
        $mime = $technical['mime_type'];
        $width = $technical['width'];
        $height = $technical['height'];
        $check['technical'] = $technical;

        $hash = hash('sha256', $bytes);
        $withdrawn = $submission->photos()->where('sha256', $hash)->whereNotNull('withdrawn_at')->lockForUpdate()->first();
        $declaration = array_replace($withdrawn ? $withdrawn->declarationData() : SubmissionDeclarations::fromMetadata($metadata), $declaration ?? []);
        $declarationCheck = SubmissionDeclarations::validate($submission->category, [
            'category_story' => $submission->category_story,
            'photos' => [['id' => (string) Str::uuid(), ...$declaration, 'position' => 1]],
        ], ! $submission->status->isEditable());
        $declaration = collect($declarationCheck['photos'][0])->only(['title', 'location', 'taken_on', 'story'])->all();
        if ($withdrawn) {
            $snapshot = $withdrawn->eligibility_snapshot ?? [];
            $snapshot['technical'] = $technical;
            $withdrawn->update(['withdrawn_at' => null, 'withdrawal_reason' => null, 'eligibility_snapshot' => $snapshot, 'declaration' => $declaration]);
            $submission->increment('details_version');
            $this->reopenEvaluations($submission);
            $this->recordRevision($submission, $withdrawn, 'photo_restored_during_evaluation');

            return $withdrawn;
        }
        if ($submission->activePhotos()->where('sha256', $hash)->lockForUpdate()->first()) {
            throw ValidationException::withMessages(['photo' => __('uye.competitions.errors.duplicate_photo')]);
        }

        $id = (string) Str::uuid();
        $extension = match ($mime) {
            'image/png' => 'png', 'image/webp' => 'webp', default => 'jpg'
        };
        $base = "competition-submissions/{$submission->entry->competition_id}/{$submission->id}/{$id}";
        $privatePath = "{$base}.{$extension}";
        $juryPath = "{$base}-jury.jpg";
        try {
            $juryBytes = $this->juryPhotos->render($bytes);
        } catch (\Throwable) {
            throw ValidationException::withMessages(['photo' => __('auth.photo_processing_failed')]);
        }

        DB::afterRollBack(fn () => Storage::disk('local')->delete([$privatePath, $juryPath]));
        try {
            if (! Storage::disk('local')->put($privatePath, $bytes)
                || ! Storage::disk('local')->put($juryPath, $juryBytes)) {
                throw new \RuntimeException('Competition photo storage failed.');
            }
            $stored = $submission->photos()->create([
                'source_photo_id' => $sourcePhotoId,
                'capture_device_id' => $captureDeviceId,
                'disk_path' => $privatePath,
                'jury_path' => $juryPath,
                'jury_sanitized_at' => now(),
                'original_filename' => $filename,
                'mime_type' => $mime,
                'file_size_bytes' => strlen($bytes),
                'width' => $width,
                'height' => $height,
                'sha256' => $hash,
                'metadata_snapshot' => $metadata,
                'declaration' => $declaration,
                'processing_method_ids' => array_values($processingMethodIds),
                'eligibility_snapshot' => $check,
                'sort_order' => ($submission->photos()->max('sort_order') ?? 0) + 10,
            ]);
        } catch (\Throwable $exception) {
            Storage::disk('local')->delete([$privatePath, $juryPath]);
            throw $exception;
        }

        $submission->increment('details_version');
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

    public function reopenForDetailsChange(CompetitionSubmission $submission): void
    {
        $this->reopenEvaluations($submission);
    }

    private function reopenEvaluations(CompetitionSubmission $submission): void
    {
        $round = $submission->entry->competition->evaluationRounds()->where('round_number', 1)->first();
        if (! $round) {
            return;
        }

        $assignmentIds = $submission->category->jurorAssignments()->pluck('id');
        CompetitionCategoryJurorAssignment::whereIn('id', $assignmentIds)->increment('evaluation_version');
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
            ->each(fn ($juror) => $juror->notify((new EvaluationReopenedNotification($submission))->afterCommit()));
    }
}
