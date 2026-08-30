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
            ->whereIn('submission_photo_id', $photoIds)
            ->whereNotNull('submitted_at')
            ->with('round:id,name,round_number')
            ->get()
            ->groupBy('submission_photo_id')
            ->map(fn (Collection $photoScores) => $photoScores
                ->groupBy('competition_evaluation_round_id')
                ->map(function (Collection $roundScores) {
                    $round = $roundScores->first()->round;
                    $jurorScores = $roundScores
                        ->groupBy('juror_assignment_id')
                        ->map(fn (Collection $scores, string $assignmentId) => [
                            'key' => hash('sha256', $roundScores->first()->submission_photo_id.':'.$assignmentId),
                            'score' => round((float) $scores->avg('score'), 2),
                        ])
                        ->sortBy('key')
                        ->values()
                        ->map(fn (array $row, int $index) => ['label' => __('uye.competitions.scorecard_evaluation', ['number' => $index + 1]), 'score' => $row['score']]);

                    return [
                        'round_name' => $round->name,
                        'round_number' => $round->round_number,
                        'average' => round((float) $jurorScores->avg('score'), 2),
                        'scores' => $jurorScores->all(),
                    ];
                })->sortBy('round_number')->values()->all())
            ->all();
    }
}
