<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['competition_evaluation_round_id', 'submission_photo_id', 'total_score', 'average_score', 'score_count', 'rank'])]
class CompetitionPhotoResult extends Model
{
    use HasUuids;

    protected function casts(): array
    {
        return ['total_score' => 'decimal:2', 'average_score' => 'decimal:2', 'score_count' => 'integer', 'rank' => 'integer'];
    }

    public function round(): BelongsTo
    {
        return $this->belongsTo(CompetitionEvaluationRound::class, 'competition_evaluation_round_id');
    }

    public function photo(): BelongsTo
    {
        return $this->belongsTo(CompetitionSubmissionPhoto::class, 'submission_photo_id');
    }

    public function awards(): HasMany
    {
        return $this->hasMany(CompetitionResultAward::class, 'competition_photo_result_id');
    }
}
