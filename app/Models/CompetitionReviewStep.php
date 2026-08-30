<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['competition_review_id', 'step_number', 'status', 'note', 'checked_at', 'addressed_at', 'addressed_by'])]
class CompetitionReviewStep extends Model
{
    use HasUuids;

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_CORRECTION_REQUIRED = 'correction_required';

    protected function casts(): array
    {
        return [
            'step_number' => 'integer',
            'checked_at' => 'datetime',
            'addressed_at' => 'datetime',
        ];
    }

    public function review(): BelongsTo
    {
        return $this->belongsTo(CompetitionReview::class, 'competition_review_id');
    }

    public function addressedBy(): BelongsTo
    {
        return $this->belongsTo(InstitutionStaff::class, 'addressed_by');
    }
}
