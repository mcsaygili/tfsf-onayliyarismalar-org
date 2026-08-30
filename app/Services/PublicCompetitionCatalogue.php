<?php

namespace App\Services;

use App\Enums\CompetitionAudience;
use App\Models\Competition;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class PublicCompetitionCatalogue
{
    public function baseQuery(): Builder
    {
        return Competition::query()
            ->publiclyVisible()
            ->with(['translations', 'institution', 'competitionType.translations'])
            ->withCount('categories');
    }

    /** @param array<string, mixed> $filters */
    public function filteredQuery(array $filters): Builder
    {
        $query = $this->baseQuery();
        $now = now();

        $query
            ->when(filled($filters['q'] ?? null), function (Builder $query) use ($filters): void {
                $term = trim((string) $filters['q']);
                $query->where(function (Builder $query) use ($term): void {
                    $query
                        ->whereHas('translations', fn (Builder $translations) => $translations->where('name', 'like', "%{$term}%"))
                        ->orWhereHas('institution', fn (Builder $institution) => $institution->where('name', 'like', "%{$term}%"));
                });
            })
            ->when(in_array($filters['audience'] ?? null, array_column(CompetitionAudience::cases(), 'value'), true),
                fn (Builder $query) => $query->where('audience', $filters['audience']))
            ->when(filled($filters['type'] ?? null),
                fn (Builder $query) => $query->whereHas('competitionType', fn (Builder $types) => $types->where('code', $filters['type'])))
            ->when(filled($filters['year'] ?? null),
                fn (Builder $query) => $query->whereYear('application_ends_at', (int) $filters['year']));

        $this->applyPhase($query, $filters['phase'] ?? null, $now);

        return $this->ordered($query, $now);
    }

    public function ordered(Builder $query, ?Carbon $now = null): Builder
    {
        $now ??= now();

        return $query
            ->orderByRaw(
                'CASE WHEN results_published_at IS NOT NULL AND results_published_at <= ? THEN 3 '
                .'WHEN application_starts_at <= ? AND application_ends_at >= ? THEN 0 '
                .'WHEN application_starts_at > ? THEN 1 ELSE 2 END',
                [$now, $now, $now, $now],
            )
            ->orderByRaw('CASE WHEN application_ends_at IS NULL THEN 1 ELSE 0 END')
            ->orderBy('application_ends_at')
            ->orderByDesc('published_at');
    }

    private function applyPhase(Builder $query, ?string $phase, Carbon $now): void
    {
        match ($phase) {
            'open' => $query
                ->whereNull('results_published_at')
                ->where('application_starts_at', '<=', $now)
                ->where('application_ends_at', '>=', $now),
            'upcoming' => $query
                ->whereNull('results_published_at')
                ->where('application_starts_at', '>', $now),
            'evaluation' => $query
                ->whereNull('results_published_at')
                ->where('application_ends_at', '<', $now),
            'completed' => $query
                ->whereNotNull('results_published_at')
                ->where('results_published_at', '<=', $now),
            default => null,
        };
    }
}
