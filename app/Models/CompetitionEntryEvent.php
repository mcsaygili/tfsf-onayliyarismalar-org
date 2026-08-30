<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable(['competition_entry_id', 'event', 'actor_type', 'actor_id', 'context'])]
class CompetitionEntryEvent extends Model
{
    use HasUuids;

    protected function casts(): array
    {
        return ['context' => 'array'];
    }

    public function entry(): BelongsTo
    {
        return $this->belongsTo(CompetitionEntry::class, 'competition_entry_id');
    }

    public function actor(): MorphTo
    {
        return $this->morphTo();
    }
}
