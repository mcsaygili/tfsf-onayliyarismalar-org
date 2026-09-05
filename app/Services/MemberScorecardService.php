<?php

namespace App\Services;

use App\Models\CompetitionEntry;
use App\Models\JuryScore;
use Illuminate\Support\Collection;

class MemberScorecardService
{
    /** @return array<string, array<int, array<string, mixed>>> */
    public function forEntry(CompetitionEntry $entry): array
    {
        $photoIds = $entry->submissions
            ->flatMap(fn ($submission) => $submission->photos->whereNull('withdrawn_at'))
            ->pluck('id');

        return JuryScore::query()
            ->weightedTotals()
            ->whereIn('submission_photo_id', $photoIds)
            ->whereNotNull('submitted_at')
            ->addSelect('jury_scores.submission_photo_id', 'jury_scores.competition_evaluation_round_id', 'jury_scores.juror_assignment_id')
            ->groupBy('jury_scores.submission_photo_id', 'jury_scores.competition_evaluation_round_id', 'jury_scores.juror_assignment_id')
            ->with('round:id,name,round_number')
            ->get()
            ->groupBy('submission_photo_id')
            ->map(fn (Collection $photoScores) => $photoScores
                ->groupBy('competition_evaluation_round_id')
                ->map(function (Collection $roundScores) {
                    $round = $roundScores->first()->round;
                    $jurorScores = $roundScores
                        ->map(fn (JuryScore $scores) => [
                            'key' => hash('sha256', $scores->submission_photo_id.':'.$scores->juror_assignment_id),
                            'score' => round((float) $scores->average_score, 2),
                        ])
                        ->sortBy('key')
                        ->values()
                        ->map(fn (array $row, int $index) => ['label' => __('uye.competitions.scorecard_evaluation', ['number' => $index + 1]), 'score' => $row['score']]);

                    return [
                        'round_name' => $round->name,
                        'round_number' => $round->round_number,
                        'average' => $roundScores->sum('total_weight') > 0
                            ? round($roundScores->sum('total_score') / $roundScores->sum('total_weight'), 2)
                            : 0.0,
                        'scores' => $jurorScores->all(),
                    ];
                })->sortBy('round_number')->values()->all())
            ->all();
    }
}
