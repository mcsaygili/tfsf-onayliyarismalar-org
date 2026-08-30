<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['competition_evaluation_round_id', 'juror_assignment_id', 'finalized_at'])]
class JuryEvaluationSubmission extends Model
{
    use HasUuids;

    protected function casts(): array
    {
        return ['finalized_at' => 'datetime'];
    }

    public function round(): BelongsTo
    {
        return $this->belongsTo(CompetitionEvaluationRound::class, 'competition_evaluation_round_id');
    }

    public function jurorAssignment(): BelongsTo
    {
        return $this->belongsTo(CompetitionCategoryJurorAssignment::class, 'juror_assignment_id');
    }
}
