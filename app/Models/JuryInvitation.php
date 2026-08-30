<?php

namespace App\Models;

use App\Enums\JuryInvitationStatus;
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
            'opened_at' => 'datetime',
            'accepted_at' => 'datetime',
            'declined_at' => 'datetime',
            'revoked_at' => 'datetime',
            'send_count' => 'integer',
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

    public function events(): HasMany
    {
        return $this->hasMany(JuryInvitationEvent::class)->latest();
    }

    public function status(): JuryInvitationStatus
    {
        return match (true) {
            $this->accepted_at !== null => JuryInvitationStatus::Accepted,
            $this->revoked_at !== null => JuryInvitationStatus::Cancelled,
            $this->declined_at !== null => JuryInvitationStatus::Declined,
            $this->expires_at?->isPast() === true => JuryInvitationStatus::Expired,
            $this->opened_at !== null => JuryInvitationStatus::Opened,
            $this->sent_at !== null => JuryInvitationStatus::Sent,
            default => JuryInvitationStatus::Draft,
        };
    }

    public function isPending(): bool
    {
        return $this->accepted_at === null && $this->declined_at === null && $this->revoked_at === null;
    }

    public function canResend(): bool
    {
        return $this->isPending() && $this->sent_at !== null;
    }

    public function canCancel(): bool
    {
        return $this->isPending();
    }
}
