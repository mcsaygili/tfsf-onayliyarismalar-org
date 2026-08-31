<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['competition_jury_session_id', 'juror_id', 'attendance_status', 'conflict_declared', 'conflict_note', 'declared_at'])]
class CompetitionJurySessionAttendance extends Model
{
    use HasUuids;

    protected function casts(): array
    {
        return ['conflict_declared' => 'boolean', 'declared_at' => 'datetime'];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(CompetitionJurySession::class, 'competition_jury_session_id');
    }

    public function juror(): BelongsTo
    {
        return $this->belongsTo(Juri::class, 'juror_id');
    }
}
