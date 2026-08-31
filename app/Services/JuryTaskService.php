<?php

namespace App\Services;

use App\Enums\CompetitionStatus;
use App\Models\Competition;
use App\Models\Juri;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

/** Jüriye atanmış yarışmaları kategori kapsamıyla birlikte hazırlar. */
class JuryTaskService
{
    /** @return Builder<Competition> */
    public function queryFor(Juri $juror): Builder
    {
        return Competition::query()
            ->with([
                'translations',
                'institution',
                'categories' => fn ($query) => $query
                    ->whereHas('jurorAssignments', fn ($assignments) => $assignments->where('juror_id', $juror->id))
                    ->with('translations'),
            ])
            ->whereHas('categories.jurorAssignments', fn ($query) => $query->where('juror_id', $juror->id))
            ->orderByRaw(
                'CASE status WHEN ? THEN 1 WHEN ? THEN 2 WHEN ? THEN 3 WHEN ? THEN 4 WHEN ? THEN 5 WHEN ? THEN 6 WHEN ? THEN 7 ELSE 8 END',
                [
                    CompetitionStatus::Approved->value,
                    CompetitionStatus::UnderReview->value,
                    CompetitionStatus::WaitingRequirements->value,
                    CompetitionStatus::Submitted->value,
                    CompetitionStatus::NeedsInfo->value,
                    CompetitionStatus::Draft->value,
                    CompetitionStatus::Rejected->value,
                ],
            )
            ->orderByDesc('application_starts_at')
            ->orderByDesc('created_at');
    }

    /**
     * Jürinin görev detayında görebileceği yarışmayı, yalnızca kendi kategori
     * kapsamıyla birlikte döndürür. Ataması bulunmayan yarışmalar 404 olur.
     */
    public function detailFor(Juri $juror, Competition $competition): Competition
    {
        return Competition::query()
            ->whereKey($competition->getKey())
            ->whereHas('categories.jurorAssignments', fn ($query) => $query->where('juror_id', $juror->id))
            ->with([
                'translations',
                'institution',
                'competitionType.translations',
                'captureRegions.country.translations',
                'captureRegions.city.translations',
                'regulationSnapshots' => fn ($query) => $query->limit(1),
                'evaluationRounds.jurySession.attendances',
                'categories' => fn ($query) => $query
                    ->whereHas('jurorAssignments', fn ($assignments) => $assignments->where('juror_id', $juror->id))
                    ->with([
                        'translations',
                        'awards.translations',
                        'awards.awardReference.translations',
                        'evaluationCriteria.criterion.translations',
                    ]),
            ])
            ->firstOrFail();
    }

    /** @return array{label: string, at: Carbon}|null */
    public function nextMilestoneFor(Juri $juror): ?array
    {
        $now = now();

        return $this->queryFor($juror)
            ->withoutEagerLoads()
            ->get(['competitions.id', 'application_starts_at', 'application_ends_at', 'competition_ends_at'])
            ->flatMap(fn (Competition $competition) => collect([
                ['label' => 'application_starts', 'at' => $competition->application_starts_at],
                ['label' => 'application_ends', 'at' => $competition->application_ends_at],
                ['label' => 'competition_ends', 'at' => $competition->competition_ends_at],
            ]))
            ->filter(fn (array $milestone) => $milestone['at']?->greaterThan($now))
            ->sortBy('at')
            ->first();
    }
}
