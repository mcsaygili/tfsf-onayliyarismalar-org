<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['competition_evaluation_round_id', 'status', 'scheduled_at', 'location', 'quorum', 'minutes', 'opened_by', 'opened_at', 'closed_by', 'closed_at'])]
class CompetitionJurySession extends Model
{
    use HasUuids;

    protected function casts(): array
    {
        return ['scheduled_at' => 'datetime', 'quorum' => 'integer', 'opened_at' => 'datetime', 'closed_at' => 'datetime'];
    }

    public function round(): BelongsTo
    {
        return $this->belongsTo(CompetitionEvaluationRound::class, 'competition_evaluation_round_id');
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(CompetitionJurySessionAttendance::class);
    }

    public function opener(): BelongsTo
    {
        return $this->belongsTo(EysUser::class, 'opened_by');
    }

    public function closer(): BelongsTo
    {
        return $this->belongsTo(EysUser::class, 'closed_by');
    }

    public function hasQuorum(): bool
    {
        return $this->attendances->where('attendance_status', 'present')->where('conflict_declared', false)->count() >= $this->quorum;
    }
}
