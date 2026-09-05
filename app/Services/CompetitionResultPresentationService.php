<?php

namespace App\Services;

use App\Models\Competition;
use App\Models\CompetitionResultPublication;

class CompetitionResultPresentationService
{
    public function translated(mixed $value): string
    {
        if (! is_array($value)) {
            return (string) ($value ?? '');
        }

        return (string) ($value[app()->getLocale()] ?? $value[config('app.fallback_locale')] ?? $value['tr'] ?? reset($value) ?: '');
    }

    public function forCompetition(Competition $competition): array
    {
        $round = $competition->evaluationRounds()->where('is_final', true)->first() ?? $competition->evaluationRounds()->orderByDesc('round_number')->firstOrFail();

        return $this->present($competition, app(ResultSnapshotBuilder::class)->build($competition, $round), null, true);
    }

    public function forPublication(CompetitionResultPublication $publication, bool $preview = false): array
    {
        return $this->present($publication->competition, $publication->snapshot, $publication, $preview);
    }

    private function present(Competition $competition, array $snapshot, ?CompetitionResultPublication $publication, bool $preview): array
    {
        $allResults = collect($snapshot['results'] ?? []);
        $assets = $publication?->assets->keyBy('source_photo_id') ?? collect();
        $categories = collect($snapshot['categories'] ?? $allResults->map(fn ($row) => ['id' => $row['category_id'], 'name' => $row['category']])->unique('id')->values()->all());
        $results = $allResults->filter(fn ($row) => ! empty($row['awards']))->map(function ($row) use ($publication, $preview, $competition, $assets) {
            $row['awards_text'] = collect($row['awards'])->map(fn ($award) => $this->translated($award['name'] ?? []))->filter()->join(' · ');
            $row['photo_url'] = $publication
                ? ($assets->has($row['photo_id']) ? ($preview
                    ? route('eys.competitions.publication-photos.show', [$competition, $publication, $row['photo_id']])
                    : route('result.publications.photos.show', [$publication, $row['photo_id']])) : null)
                : route('eys.competitions.results.photos.show', [$competition, $row['photo_id']]);

            return $row;
        });

        return [
            'competition' => $competition,
            'display' => [
                'name' => $this->translated(data_get($snapshot, 'competition.name', [])),
                'subject' => $this->translated(data_get($snapshot, 'competition.subject', [])),
                'institution' => data_get($snapshot, 'competition.institution', ''),
                'type' => $this->translated(data_get($snapshot, 'competition.type', [])),
                'categories' => $categories->map(fn ($category) => ['id' => $category['id'], 'name' => $this->translated($category['name'])]),
                'category_count' => $categories->count(),
                'participant_count' => $snapshot['participant_count'] ?? null,
                'photo_count' => $snapshot['photo_count'] ?? null,
                'results' => $results->groupBy('category_id'),
                'awarded_count' => $results->count(),
                'participants' => $results->unique(fn ($row) => $row['participant_id'] ?? $row['participant'])->pluck('participant'),
                'round' => data_get($snapshot, 'round.name', ''),
                'version' => $publication?->version,
                'published_at' => $publication?->published_at,
                'partial' => $publication && $publication->snapshot_version < 2,
            ],
        ];
    }
}
