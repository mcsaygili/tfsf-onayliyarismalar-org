<?php

namespace App\Services;

use App\Models\Competition;
use App\Models\CompetitionCategoryJurorAssignment;
use App\Support\CompetitionWizard\CompetitionStepRegistry;
use Illuminate\Database\Eloquent\Collection;

/** Yarışmanın kurum gönderimi ve EYS onayı öncesindeki ortak hazırlık denetimleri. */
class CompetitionReadinessService
{
    /**
     * Adım 11 sunum adımı olduğu için gönderim kontrolünde 1–10 değerlendirilir.
     *
     * @return array<int, array{number: int, label: string, status: string, blocking: bool}>
     */
    public function submissionChecks(Competition $competition): array
    {
        return collect(CompetitionStepRegistry::all())
            ->reject(fn ($step) => $step->number() === 11)
            ->map(function ($step) use ($competition): array {
                if (! $step->isApplicable($competition)) {
                    return [
                        'number' => $step->number(),
                        'label' => $step->label(),
                        'status' => 'not_applicable',
                        'blocking' => false,
                    ];
                }

                if (! $step->isImplemented()) {
                    return [
                        'number' => $step->number(),
                        'label' => $step->label(),
                        'status' => 'unavailable',
                        'blocking' => true,
                    ];
                }

                $complete = CompetitionStepRegistry::stepIsComplete($competition, $step);

                return [
                    'number' => $step->number(),
                    'label' => $step->label(),
                    'status' => $complete ? 'complete' : 'incomplete',
                    'blocking' => ! $complete,
                ];
            })
            ->values()
            ->all();
    }

    /** @return array<int, array{number: int, label: string, status: string, blocking: bool}> */
    public function submissionBlockers(Competition $competition): array
    {
        return array_values(array_filter(
            $this->submissionChecks($competition),
            fn (array $check): bool => $check['blocking'],
        ));
    }

    /**
     * Kurum gönderimini engellemez; EYS onayının beklemesine neden olur.
     *
     * @return Collection<int, CompetitionCategoryJurorAssignment>
     */
    public function pendingJuryAssignments(Competition $competition): Collection
    {
        return CompetitionCategoryJurorAssignment::query()
            ->whereNull('juror_id')
            ->whereHas('category', fn ($query) => $query->where('competition_id', $competition->id))
            ->with(['category.translations', 'invitation'])
            ->orderBy('competition_category_id')
            ->orderBy('sort_order')
            ->get();
    }

    public function allJurorsRegistered(Competition $competition): bool
    {
        return ! CompetitionCategoryJurorAssignment::query()
            ->whereNull('juror_id')
            ->whereHas('category', fn ($query) => $query->where('competition_id', $competition->id))
            ->exists();
    }
}
