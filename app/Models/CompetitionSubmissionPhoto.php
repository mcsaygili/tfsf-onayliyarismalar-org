<?php

namespace App\Models;

use App\Support\Photo\SubmissionDeclarations;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['competition_submission_id', 'source_photo_id', 'capture_device_id', 'disk_path', 'jury_path', 'jury_sanitized_at', 'original_filename', 'mime_type', 'file_size_bytes', 'width', 'height', 'sha256', 'metadata_snapshot', 'processing_method_ids', 'eligibility_snapshot', 'sort_order', 'withdrawn_at', 'withdrawal_reason', 'declaration'])]
class CompetitionSubmissionPhoto extends Model
{
    use HasUuids;

    protected function casts(): array
    {
        return [
            'declaration' => 'array',
            'metadata_snapshot' => 'array',
            'processing_method_ids' => 'array',
            'eligibility_snapshot' => 'array',
            'file_size_bytes' => 'integer',
            'width' => 'integer',
            'height' => 'integer',
            'sort_order' => 'integer',
            'withdrawn_at' => 'datetime',
            'jury_sanitized_at' => 'datetime',
        ];
    }

    public function declarationData(): array
    {
        return array_replace(['title' => null, 'location' => null, 'taken_on' => null, 'story' => null],
            $this->declaration ?? SubmissionDeclarations::fromMetadata($this->metadata_snapshot ?? []));
    }

    public function submission(): BelongsTo
    {
        return $this->belongsTo(CompetitionSubmission::class, 'competition_submission_id');
    }

    public function sourcePhoto(): BelongsTo
    {
        return $this->belongsTo(Photo::class, 'source_photo_id');
    }

    public function captureDevice(): BelongsTo
    {
        return $this->belongsTo(CaptureDevice::class)->withTrashed();
    }

    public function scores(): HasMany
    {
        return $this->hasMany(JuryScore::class, 'submission_photo_id');
    }

    public function results(): HasMany
    {
        return $this->hasMany(CompetitionPhotoResult::class, 'submission_photo_id');
    }

    public function committeeDecisions(): HasMany
    {
        return $this->hasMany(CompetitionCommitteeDecision::class, 'submission_photo_id');
    }
}
