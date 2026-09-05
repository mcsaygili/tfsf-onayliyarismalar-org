<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['competition_evaluation_round_id', 'juror_assignment_id', 'submission_photo_id', 'criterion_assignment_id', 'score', 'submitted_at'])]
class JuryScore extends Model
{
    use HasUuids;

    public function scopeWeightedTotals(Builder $query): Builder
    {
        return $query
            ->join('competition_category_evaluation_criteria as criteria', 'criteria.id', '=', 'jury_scores.criterion_assignment_id')
            ->selectRaw('SUM(jury_scores.score * criteria.weight) as total_score, SUM(criteria.weight) as total_weight, 1.0 * SUM(jury_scores.score * criteria.weight) / NULLIF(SUM(criteria.weight), 0) as average_score, COUNT(*) as score_count');
    }

    protected function casts(): array
    {
        return ['score' => 'integer', 'submitted_at' => 'datetime'];
    }

    public function round(): BelongsTo
    {
        return $this->belongsTo(CompetitionEvaluationRound::class, 'competition_evaluation_round_id');
    }

    public function jurorAssignment(): BelongsTo
    {
        return $this->belongsTo(CompetitionCategoryJurorAssignment::class, 'juror_assignment_id');
    }

    public function photo(): BelongsTo
    {
        return $this->belongsTo(CompetitionSubmissionPhoto::class, 'submission_photo_id');
    }

    public function criterionAssignment(): BelongsTo
    {
        return $this->belongsTo(CompetitionCategoryEvaluationCriterion::class, 'criterion_assignment_id');
    }
}
