<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Bir yarışma başvurusunun onay sürecindeki tek bir denetim kaydı —
 * append-only, hiçbir satır update edilmiyor (bkz. proje planı).
 */
#[Fillable(['competition_id', 'action', 'from_status', 'to_status', 'message', 'changes', 'actor_id', 'actor_type', 'actor_guard', 'request_id', 'ip_address', 'user_agent'])]
class CompetitionStatusLog extends Model
{
    use HasFactory, HasUuids;

    protected function casts(): array
    {
        return [
            'changes' => 'array',
        ];
    }

    public function competition(): BelongsTo
    {
        return $this->belongsTo(Competition::class);
    }

    public function actor(): MorphTo
    {
        return $this->morphTo();
    }
}
