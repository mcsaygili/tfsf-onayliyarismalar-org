<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'competition_id', 'institution_id', 'invited_by', 'accepted_juror_id',
    'email', 'first_name', 'last_name', 'locale',
])]
class JuryInvitation extends Model
{
    use HasUuids;

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'sent_at' => 'datetime',
            'accepted_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public function competition(): BelongsTo
    {
        return $this->belongsTo(Competition::class);
    }

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    public function inviter(): BelongsTo
    {
        return $this->belongsTo(InstitutionStaff::class, 'invited_by');
    }

    public function acceptedJuror(): BelongsTo
    {
        return $this->belongsTo(Juri::class, 'accepted_juror_id');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(CompetitionCategoryJurorAssignment::class);
    }

    public function isPending(): bool
    {
        return $this->accepted_at === null && $this->revoked_at === null;
    }
}
