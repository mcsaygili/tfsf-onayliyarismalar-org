<?php

namespace App\Models;

use App\Enums\CompetitionSubmissionStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['competition_entry_id', 'competition_category_id', 'status', 'eligibility_snapshot', 'submitted_at', 'approved_at', 'rejected_at', 'rejection_reason', 'category_story'])]
class CompetitionSubmission extends Model
{
    use HasUuids;

    protected static function booted(): void
    {
        static::creating(function (self $submission): void {
            do {
                $code = strtoupper(bin2hex(random_bytes(8)));
            } while (self::query()->where('series_code', $code)->exists());
            $submission->series_code = $code;
        });
        static::updating(function (self $submission): void {
            if ($submission->isDirty('series_code')) {
                throw new \LogicException('A submission series code cannot be changed.');
            }
        });
    }

    public function seriesCode(): string
    {
        return 'S-'.implode('-', str_split($this->series_code, 4));
    }

    protected function casts(): array
    {
        return [
            'details_version' => 'integer',
            'status' => CompetitionSubmissionStatus::class,
            'eligibility_snapshot' => 'array',
            'submitted_at' => 'datetime',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
        ];
    }

    public function entry(): BelongsTo
    {
        return $this->belongsTo(CompetitionEntry::class, 'competition_entry_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(CompetitionCategory::class, 'competition_category_id');
    }

    public function photos(): HasMany
    {
        return $this->hasMany(CompetitionSubmissionPhoto::class)->orderBy('sort_order');
    }

    public function activePhotos(): HasMany
    {
        return $this->hasMany(CompetitionSubmissionPhoto::class)->whereNull('withdrawn_at')->orderBy('sort_order');
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(CompetitionSubmissionApproval::class)->orderBy('sequence');
    }
}
