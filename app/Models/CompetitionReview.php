<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['competition_id', 'round', 'reviewer_id', 'status', 'started_at', 'completed_at'])]
class CompetitionReview extends Model
{
    use HasUuids;

    protected function casts(): array
    {
        return [
            'round' => 'integer',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function competition(): BelongsTo
    {
        return $this->belongsTo(Competition::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(EysUser::class, 'reviewer_id');
    }

    public function steps(): HasMany
    {
        return $this->hasMany(CompetitionReviewStep::class)->orderBy('step_number');
    }

    public function hasPendingDecisions(): bool
    {
        return $this->steps->contains('status', CompetitionReviewStep::STATUS_PENDING);
    }

    public function hasCorrections(): bool
    {
        return $this->steps->contains('status', CompetitionReviewStep::STATUS_CORRECTION_REQUIRED);
    }

    public function isFullyApproved(): bool
    {
        return $this->steps->isNotEmpty()
            && $this->steps->every(fn (CompetitionReviewStep $step) => $step->status === CompetitionReviewStep::STATUS_APPROVED);
    }
}
