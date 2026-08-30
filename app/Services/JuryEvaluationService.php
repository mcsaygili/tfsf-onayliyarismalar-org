<?php

namespace App\Services;

use App\Enums\CompetitionSubmissionStatus;
use App\Enums\EvaluationRoundStatus;
use App\Models\Competition;
use App\Models\CompetitionCategoryJurorAssignment;
use App\Models\CompetitionEvaluationRound;
use App\Models\Juri;
use App\Models\JuryEvaluationSubmission;
use App\Models\JuryScore;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class JuryEvaluationService
{
    public function roundFor(Competition $competition): CompetitionEvaluationRound
    {
        return $competition->evaluationRounds()->firstOrCreate(
            ['round_number' => 1],
            [
                'name' => 'Genel Değerlendirme',
                'status' => EvaluationRoundStatus::Open,
                'opens_at' => $competition->evaluation_starts_at,
                'closes_at' => $competition->evaluation_ends_at,
            ],
        );
    }

    public function assignmentFor(Juri $juror, Competition $competition, string $categoryId): CompetitionCategoryJurorAssignment
    {
        return CompetitionCategoryJurorAssignment::query()
            ->where('juror_id', $juror->id)
            ->where('competition_category_id', $categoryId)
            ->whereHas('category', fn ($query) => $query->where('competition_id', $competition->id))
            ->with(['category.translations', 'category.evaluationCriteria.criterion.translations'])
            ->firstOrFail();
    }

    public function evaluationData(CompetitionCategoryJurorAssignment $assignment, CompetitionEvaluationRound $round): array
    {
        $photos = $assignment->category->submissions()
            ->where('status', CompetitionSubmissionStatus::Approved)
            ->with('photos')
            ->get()
            ->flatMap->photos
            ->values();
        $scores = JuryScore::query()
            ->where('competition_evaluation_round_id', $round->id)
            ->where('juror_assignment_id', $assignment->id)
            ->get()
            ->keyBy(fn ($score) => $score->submission_photo_id.':'.$score->criterion_assignment_id);

        return [
            'photos' => $photos,
            'scores' => $scores,
            'finalized' => JuryEvaluationSubmission::where('competition_evaluation_round_id', $round->id)
                ->where('juror_assignment_id', $assignment->id)->exists(),
        ];
    }

    public function save(CompetitionCategoryJurorAssignment $assignment, CompetitionEvaluationRound $round, array $scores): void
    {
        if ($round->status !== EvaluationRoundStatus::Open) {
            throw ValidationException::withMessages(['scores' => __('juri.evaluation.errors.round_closed')]);
        }
        if (JuryEvaluationSubmission::where('competition_evaluation_round_id', $round->id)->where('juror_assignment_id', $assignment->id)->exists()) {
            throw ValidationException::withMessages(['scores' => __('juri.evaluation.errors.finalized')]);
        }

        $criteria = $assignment->category->evaluationCriteria->keyBy('id');
        $photoIds = $assignment->category->submissions()->where('status', CompetitionSubmissionStatus::Approved)
            ->with('photos:id,competition_submission_id')->get()->flatMap->photos->pluck('id')->all();

        DB::transaction(function () use ($scores, $criteria, $photoIds, $assignment, $round) {
            foreach ($scores as $photoId => $criterionScores) {
                if (! in_array($photoId, $photoIds, true) || ! is_array($criterionScores)) {
                    continue;
                }
                foreach ($criterionScores as $criterionId => $score) {
                    $criterion = $criteria->get($criterionId);
                    if (! $criterion || ! is_numeric($score) || (int) $score < $criterion->min_score || (int) $score > $criterion->max_score) {
                        throw ValidationException::withMessages(['scores' => __('juri.evaluation.errors.invalid_score')]);
                    }
                    JuryScore::updateOrCreate([
                        'competition_evaluation_round_id' => $round->id,
                        'juror_assignment_id' => $assignment->id,
                        'submission_photo_id' => $photoId,
                        'criterion_assignment_id' => $criterionId,
                    ], ['score' => (int) $score]);
                }
            }
        });
    }

    public function finalize(CompetitionCategoryJurorAssignment $assignment, CompetitionEvaluationRound $round): void
    {
        if ($round->status !== EvaluationRoundStatus::Open) {
            throw ValidationException::withMessages(['scores' => __('juri.evaluation.errors.round_closed')]);
        }

        $data = $this->evaluationData($assignment, $round);
        $expected = $data['photos']->count() * $assignment->category->evaluationCriteria->count();
        $actual = JuryScore::where('competition_evaluation_round_id', $round->id)
            ->where('juror_assignment_id', $assignment->id)
            ->whereIn('submission_photo_id', $data['photos']->pluck('id'))
            ->count();
        if ($expected === 0 || $actual !== $expected) {
            throw ValidationException::withMessages(['scores' => __('juri.evaluation.errors.incomplete')]);
        }

        DB::transaction(function () use ($assignment, $round) {
            JuryEvaluationSubmission::firstOrCreate([
                'competition_evaluation_round_id' => $round->id,
                'juror_assignment_id' => $assignment->id,
            ], ['finalized_at' => now()]);
            JuryScore::where('competition_evaluation_round_id', $round->id)
                ->where('juror_assignment_id', $assignment->id)
                ->update(['submitted_at' => now()]);
        });
    }
}
