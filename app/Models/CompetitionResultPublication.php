<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['competition_id', 'version', 'snapshot', 'publication_note', 'correction_note', 'published_by', 'published_at', 'notified_at', 'withdrawn_at', 'snapshot_version', 'search_text'])]
class CompetitionResultPublication extends Model
{
    use HasUuids;

    protected function casts(): array
    {
        return ['snapshot_version' => 'integer', 'version' => 'integer', 'snapshot' => 'array', 'published_at' => 'datetime', 'notified_at' => 'datetime', 'withdrawn_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::deleting(fn () => throw new \LogicException('Published result records must be retained.'));
        static::updating(function (self $publication): void {
            if ($publication->isDirty(['competition_id', 'version', 'snapshot', 'snapshot_version', 'search_text', 'publication_note', 'published_by', 'published_at'])) {
                throw new \LogicException('Published result content cannot be rewritten.');
            }
        });
    }

    public function assets()
    {
        return $this->hasMany(CompetitionResultAsset::class, 'publication_id');
    }

    public function scopeCurrentPublic($query)
    {
        return $query->whereNull('withdrawn_at')->where('published_at', '<=', now())
            ->whereHas('competition', fn ($competition) => $competition->publiclyVisible()
                ->whereNotNull('results_published_at')->where('results_published_at', '<=', now())
                ->whereColumn('competitions.results_publication_version', 'competition_result_publications.version'));
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
