<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['competition_id', 'version', 'snapshot', 'publication_note', 'correction_note', 'published_by', 'published_at', 'notified_at', 'withdrawn_at'])]
class CompetitionResultPublication extends Model
{
    use HasUuids;

    protected function casts(): array
    {
        return ['version' => 'integer', 'snapshot' => 'array', 'published_at' => 'datetime', 'notified_at' => 'datetime', 'withdrawn_at' => 'datetime'];
    }

    public function competition(): BelongsTo
    {
        return $this->belongsTo(Competition::class);
    }

    public function publisher(): BelongsTo
    {
        return $this->belongsTo(EysUser::class, 'published_by');
    }
}
