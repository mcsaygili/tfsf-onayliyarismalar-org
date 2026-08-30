<?php

namespace App\Models;

use App\Enums\CompetitionSubmissionStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['competition_entry_id', 'competition_category_id', 'status', 'eligibility_snapshot', 'submitted_at', 'approved_at', 'rejected_at', 'rejection_reason'])]
class CompetitionSubmission extends Model
{
    use HasUuids;

    protected function casts(): array
    {
        return [
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

    public function approvals(): HasMany
    {
        return $this->hasMany(CompetitionSubmissionApproval::class)->orderBy('sequence');
    }
}
