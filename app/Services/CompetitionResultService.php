<?php

namespace App\Services;

use App\Models\CompetitionEvaluationRound;
use App\Models\CompetitionPhotoResult;
use App\Models\JuryScore;
use Illuminate\Support\Facades\DB;

class CompetitionResultService
{
    public function aggregate(CompetitionEvaluationRound $round): void
    {
        DB::transaction(function () use ($round) {
            $aggregates = JuryScore::query()
                ->join('competition_category_evaluation_criteria as criteria', 'criteria.id', '=', 'jury_scores.criterion_assignment_id')
                ->join('competition_submission_photos as photos', 'photos.id', '=', 'jury_scores.submission_photo_id')
                ->join('competition_submissions as submissions', 'submissions.id', '=', 'photos.competition_submission_id')
                ->where('jury_scores.competition_evaluation_round_id', $round->id)
                ->whereNotNull('jury_scores.submitted_at')
                ->whereNull('photos.withdrawn_at')
                ->selectRaw('jury_scores.submission_photo_id, submissions.competition_category_id, SUM(score * criteria.weight) as total_score, SUM(score * criteria.weight) / SUM(criteria.weight) as average_score, COUNT(*) as score_count')
                ->groupBy('jury_scores.submission_photo_id', 'submissions.competition_category_id')
                ->orderBy('submissions.competition_category_id')
                ->orderByDesc('total_score')
                ->orderBy('jury_scores.submission_photo_id')
                ->get();

            $rank = 0;
            $previousTotal = null;
            $previousCategory = null;
            foreach ($aggregates as $index => $aggregate) {
                if ($previousCategory !== $aggregate->competition_category_id) {
                    $rank = 1;
                    $previousTotal = $aggregate->total_score;
                    $previousCategory = $aggregate->competition_category_id;
                    $categoryIndex = 1;
                } else {
                    $categoryIndex++;
                }
                if ((float) $previousTotal !== (float) $aggregate->total_score) {
                    $rank = $categoryIndex;
                    $previousTotal = $aggregate->total_score;
                }
                CompetitionPhotoResult::updateOrCreate([
                    'competition_evaluation_round_id' => $round->id,
                    'submission_photo_id' => $aggregate->submission_photo_id,
                ], [
                    'total_score' => $aggregate->total_score,
                    'average_score' => $aggregate->average_score,
                    'score_count' => $aggregate->score_count,
                    'rank' => $rank,
                ]);
            }

            CompetitionPhotoResult::query()
                ->where('competition_evaluation_round_id', $round->id)
                ->whereNotIn('submission_photo_id', $aggregates->pluck('submission_photo_id'))
                ->delete();
        });
    }

    public function aggregateCommittee(CompetitionEvaluationRound $round): void
    {
        DB::transaction(function () use ($round) {
            $decisions = $round->committeeDecisions()
                ->where('decision', 'selected')
                ->whereHas('photo', fn ($query) => $query->whereNull('withdrawn_at'))
                ->with('photo.submission')
                ->orderByRaw('rank is null')
                ->orderBy('rank')
                ->orderByDesc('score')
                ->get();

            foreach ($decisions as $decision) {
                CompetitionPhotoResult::updateOrCreate([
                    'competition_evaluation_round_id' => $round->id,
                    'submission_photo_id' => $decision->submission_photo_id,
                ], [
                    'total_score' => $decision->score ?? 0,
                    'average_score' => $decision->score ?? 0,
                    'score_count' => $decision->score === null ? 0 : 1,
                    'rank' => $decision->rank,
                ]);
            }

            CompetitionPhotoResult::query()
                ->where('competition_evaluation_round_id', $round->id)
                ->whereNotIn('submission_photo_id', $decisions->pluck('submission_photo_id'))
                ->delete();
        });
    }
}
