<?php

namespace App\Models;

use App\Enums\CommitteeDecisionStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['competition_evaluation_round_id', 'submission_photo_id', 'decision', 'score', 'rank', 'note', 'decided_by', 'decided_at'])]
class CompetitionCommitteeDecision extends Model
{
    use HasUuids;

    protected function casts(): array
    {
        return [
            'decision' => CommitteeDecisionStatus::class,
            'score' => 'integer',
            'rank' => 'integer',
            'decided_at' => 'datetime',
        ];
    }

    public function round(): BelongsTo
    {
        return $this->belongsTo(CompetitionEvaluationRound::class, 'competition_evaluation_round_id');
    }

    public function photo(): BelongsTo
    {
        return $this->belongsTo(CompetitionSubmissionPhoto::class, 'submission_photo_id');
    }

    public function decidedBy(): BelongsTo
    {
        return $this->belongsTo(EysUser::class, 'decided_by');
    }
}
