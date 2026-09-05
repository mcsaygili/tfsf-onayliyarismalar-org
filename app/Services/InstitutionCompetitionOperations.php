<?php

namespace App\Services;

use App\Models\Competition;
use App\Models\CompetitionEntry;
use App\Models\CompetitionSubmission;
use App\Models\InstitutionStaff;
use Illuminate\Database\Eloquent\Builder;

class InstitutionCompetitionOperations
{
    public function competitions(InstitutionStaff $actor): Builder
    {
        return app(InstitutionCompetitionAccess::class)->scope(Competition::query(), $actor);
    }

    public function submissions(Competition $competition, array $filters = []): Builder
    {
        return CompetitionSubmission::query()->whereNotNull('submitted_at')
            ->whereHas('entry', fn ($q) => $q->where('competition_id', $competition->id)->whereNotNull('submitted_at'))
            ->when($filters['category'] ?? null, fn ($q, $id) => $q->where('competition_category_id', $id))
            ->when($filters['status'] ?? null, fn ($q, $status) => $q->where('status', $status));
    }

    public function participants(Competition $competition, array $filters): Builder
    {
        $submissions = $this->submissions($competition, $filters);

        return CompetitionEntry::query()->where('competition_id', $competition->id)->whereNotNull('submitted_at')
            ->whereIn('id', (clone $submissions)->select('competition_entry_id'))
            ->select(['id', 'user_id', 'status', 'submitted_at'])
            ->with(['user:id,first_name,last_name,country_id,city_id', 'user.country.translations', 'user.city.translations',
                'submissions' => fn ($q) => $q->whereIn('id', (clone $submissions)->select('id'))
                    ->select(['id', 'competition_entry_id', 'competition_category_id', 'status', 'submitted_at'])
                    ->with('category.translations')->withCount('activePhotos')])
            ->orderByDesc('submitted_at')->orderBy('id');
    }

    public function statistics(Competition $competition, array $filters): array
    {
        $submissions = $this->submissions($competition, $filters);
        $counts = (clone $submissions)->selectRaw('competition_category_id, status, COUNT(*) AS submissions, COUNT(DISTINCT competition_entry_id) AS participants')
            ->groupBy('competition_category_id', 'status')->get();
        $photos = (clone $submissions)->join('competition_submission_photos as p', 'p.competition_submission_id', '=', 'competition_submissions.id')
            ->whereNull('p.withdrawn_at')->selectRaw('competition_category_id, COUNT(*) AS photos')
            ->groupBy('competition_category_id')->pluck('photos', 'competition_category_id');
        $categories = $competition->categories()->with('translations')->orderBy('sort_order')->orderBy('id')->get()
            ->when($filters['category'] ?? null, fn ($items, $id) => $items->where('id', $id));

        return [
            'participants' => (clone $submissions)->distinct()->count('competition_entry_id'),
            'submissions' => (int) $counts->sum('submissions'), 'photos' => (int) $photos->sum(),
            'statuses' => $counts->groupBy('status')->map(fn ($rows) => (int) $rows->sum('submissions')),
            'categories' => $categories->map(fn ($category) => [
                'id' => $category->id, 'name' => $category->name,
                'participants' => (int) $counts->where('competition_category_id', $category->id)->sum('participants'),
                'submissions' => (int) $counts->where('competition_category_id', $category->id)->sum('submissions'),
                'photos' => (int) ($photos[$category->id] ?? 0),
            ]),
        ];
    }
}
