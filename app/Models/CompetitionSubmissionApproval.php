<?php

namespace App\Models;

use App\Enums\SubmissionApprovalStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable(['competition_submission_id', 'approval_type', 'status', 'sequence', 'reviewed_by_type', 'reviewed_by_id', 'note', 'reviewed_at'])]
class CompetitionSubmissionApproval extends Model
{
    use HasUuids;

    protected function casts(): array
    {
        return ['status' => SubmissionApprovalStatus::class, 'sequence' => 'integer', 'reviewed_at' => 'datetime'];
    }

    public function submission(): BelongsTo
    {
        return $this->belongsTo(CompetitionSubmission::class, 'competition_submission_id');
    }

    public function reviewedBy(): MorphTo
    {
        return $this->morphTo();
    }
}
