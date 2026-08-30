<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable(['jury_invitation_id', 'action', 'actor_id', 'actor_type', 'metadata'])]
class JuryInvitationEvent extends Model
{
    use HasUuids;

    protected function casts(): array
    {
        return ['metadata' => 'array'];
    }

    public function invitation(): BelongsTo
    {
        return $this->belongsTo(JuryInvitation::class, 'jury_invitation_id');
    }

    public function actor(): MorphTo
    {
        return $this->morphTo();
    }
}
