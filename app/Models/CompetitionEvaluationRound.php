<?php

namespace App\Models;

use App\Enums\EvaluationRoundMethod;
use App\Enums\EvaluationRoundStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['competition_id', 'round_number', 'name', 'method', 'is_final', 'status', 'opens_at', 'closes_at', 'finalized_at'])]
class CompetitionEvaluationRound extends Model
{
    use HasUuids;

    protected function casts(): array
    {
        return ['status' => EvaluationRoundStatus::class, 'method' => EvaluationRoundMethod::class, 'is_final' => 'boolean', 'round_number' => 'integer', 'opens_at' => 'datetime', 'closes_at' => 'datetime', 'finalized_at' => 'datetime'];
    }

    public function competition(): BelongsTo
    {
        return $this->belongsTo(Competition::class);
    }

    public function scores(): HasMany
    {
        return $this->hasMany(JuryScore::class);
    }

    public function evaluationSubmissions(): HasMany
    {
        return $this->hasMany(JuryEvaluationSubmission::class);
    }

    public function results(): HasMany
    {
        return $this->hasMany(CompetitionPhotoResult::class);
    }

    public function committeeDecisions(): HasMany
    {
        return $this->hasMany(CompetitionCommitteeDecision::class);
    }

    public function jurySession(): HasOne
    {
        return $this->hasOne(CompetitionJurySession::class);
    }
}
