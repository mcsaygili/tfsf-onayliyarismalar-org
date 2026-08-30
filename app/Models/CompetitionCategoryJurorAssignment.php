<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['competition_category_id', 'juror_id', 'jury_invitation_id', 'assigned_by', 'sort_order'])]
class CompetitionCategoryJurorAssignment extends Model
{
    use HasUuids;

    public function category(): BelongsTo
    {
        return $this->belongsTo(CompetitionCategory::class, 'competition_category_id');
    }

    public function juror(): BelongsTo
    {
        return $this->belongsTo(Juri::class, 'juror_id');
    }

    public function invitation(): BelongsTo
    {
        return $this->belongsTo(JuryInvitation::class, 'jury_invitation_id');
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(InstitutionStaff::class, 'assigned_by');
    }

    public function scores(): HasMany
    {
        return $this->hasMany(JuryScore::class, 'juror_assignment_id');
    }

    public function evaluationSubmissions(): HasMany
    {
        return $this->hasMany(JuryEvaluationSubmission::class, 'juror_assignment_id');
    }
}
