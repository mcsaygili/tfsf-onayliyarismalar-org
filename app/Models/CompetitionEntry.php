<?php

namespace App\Models;

use App\Enums\CompetitionEntryStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['competition_id', 'user_id', 'status', 'eligibility_snapshot', 'regulation_snapshot_id', 'consent_at', 'submitted_at', 'approved_at', 'rejected_at', 'withdrawn_at'])]
class CompetitionEntry extends Model
{
    use HasUuids;

    protected function casts(): array
    {
        return [
            'status' => CompetitionEntryStatus::class,
            'eligibility_snapshot' => 'array',
            'consent_at' => 'datetime',
            'submitted_at' => 'datetime',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
            'withdrawn_at' => 'datetime',
        ];
    }

    public function competition(): BelongsTo
    {
        return $this->belongsTo(Competition::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function regulationSnapshot(): BelongsTo
    {
        return $this->belongsTo(CompetitionRegulationSnapshot::class);
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(CompetitionSubmission::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(CompetitionEntryEvent::class)->latest();
    }
}
