<?php

namespace App\Services;

use App\Enums\CompetitionOperationalPhase;
use App\Enums\CompetitionSubmissionStatus;
use App\Enums\EvaluationRoundMethod;
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
        return DB::transaction(function () use ($competition) {
            $competition = CompetitionMutationLock::acquire($competition->id);

            return $competition->evaluationRounds()->firstOrCreate(['round_number' => 1], [
                'name' => 'Genel Değerlendirme', 'method' => EvaluationRoundMethod::Individual, 'is_final' => false,
                'status' => EvaluationRoundStatus::Open, 'opens_at' => $competition->evaluation_starts_at, 'closes_at' => $competition->evaluation_ends_at,
            ]);
        });
    }

    public function assignmentFor(Juri $juror, Competition $competition, string $categoryId): CompetitionCategoryJurorAssignment
    {
        return CompetitionCategoryJurorAssignment::query()->where('juror_id', $juror->id)->where('competition_category_id', $categoryId)
            ->whereHas('category', fn ($query) => $query->where('competition_id', $competition->id))
            ->with(['category.translations', 'category.evaluationCriteria.criterion.translations'])->firstOrFail();
    }

    public function evaluationData(CompetitionCategoryJurorAssignment $assignment, CompetitionEvaluationRound $round): array
    {
        $competitionId = $assignment->category->competition_id;

        return DB::transaction(function () use ($competitionId, $assignment, $round) {
            $competition = CompetitionMutationLock::acquire($competitionId);
            $current = $this->currentAssignment($assignment);
            $round = CompetitionEvaluationRound::whereKey($round->id)->where('competition_id', $competitionId)->firstOrFail();

            return $this->data($current, $round, $competition);
        });
    }

    private function currentAssignment(CompetitionCategoryJurorAssignment $assignment): CompetitionCategoryJurorAssignment
    {
        return CompetitionCategoryJurorAssignment::whereKey($assignment->id)->where('juror_id', $assignment->juror_id)
            ->with(['category.evaluationCriteria.criterion', 'juror'])->firstOrFail();
    }

    private function data(CompetitionCategoryJurorAssignment $assignment, CompetitionEvaluationRound $round, Competition $competition): array
    {
        $submissions = $assignment->category->submissions()->where('status', CompetitionSubmissionStatus::Approved)
            ->orderBy($assignment->category->photos_grouped ? 'series_code' : 'id')->with(['photos' => fn ($query) => $query->whereNull('withdrawn_at')->orderBy('id')])->get();
        $photos = $submissions->flatMap(fn ($submission) => $submission->photos->each(fn ($photo) => $photo->setRelation('submission', $submission)))->values();
        $scores = JuryScore::where('competition_evaluation_round_id', $round->id)->where('juror_assignment_id', $assignment->id)
            ->get()->keyBy(fn ($score) => $score->submission_photo_id.':'.$score->criterion_assignment_id);
        $state = [
            'assignment' => [$assignment->id, $assignment->juror_id, $assignment->evaluation_version],
            'round' => [$round->id, $round->status->value, $round->method->value, $round->is_final],
            'competition' => $competition->only(['status', 'application_ends_at', 'evaluation_starts_at', 'evaluation_ends_at', 'competition_ends_at', 'results_published_at']),
            'criteria' => $assignment->category->evaluationCriteria->sortBy('id')->map->only(['id', 'evaluation_criterion_id', 'min_score', 'max_score', 'weight'])->values()->all(),
            'category' => $assignment->category->only(['photo_story_required', 'category_story_required', 'photo_order_required', 'photos_grouped', 'photo_rules']),
            'submissions' => $submissions->map->only(['id', 'details_version', 'category_story', 'series_code'])->all(),
            'photos' => $photos->map->only(['id', 'sha256', 'sort_order', 'declaration'])->all(),
        ];

        return ['assignment' => $assignment, 'round' => $round, 'competition' => $competition,
            'phase' => app(CompetitionPhaseService::class)->phase($competition),
            'photos' => $photos, 'scores' => $scores,
            'photoGroups' => $assignment->category->photos_grouped ? $submissions->map(fn ($submission) => $submission->photos->pluck('id')->all())->values()->all() : [],
            'evaluationLocked' => $round->status !== EvaluationRoundStatus::Open || $round->method !== EvaluationRoundMethod::Individual || $round->is_final
                || $competition->evaluationRounds()->where(fn ($query) => $query->where('is_final', true)->orWhere('round_number', '>', 1))->exists(),
            'evaluationContext' => hash_hmac('sha256', json_encode($state, JSON_THROW_ON_ERROR), config('app.key')),
            'finalized' => JuryEvaluationSubmission::where('competition_evaluation_round_id', $round->id)->where('juror_assignment_id', $assignment->id)->exists()];
    }

    public function save(CompetitionCategoryJurorAssignment $assignment, CompetitionEvaluationRound $round, array $scores, string $context, bool $finalize = false): void
    {
        $competitionId = $assignment->category->competition_id;
        DB::transaction(function () use ($competitionId, $assignment, $round, $scores, $context, $finalize) {
            $competition = CompetitionMutationLock::acquire($competitionId);
            $assignment = $this->currentAssignment($assignment);
            $round = CompetitionEvaluationRound::whereKey($round->id)->where('competition_id', $competitionId)->firstOrFail();
            if ($round->status !== EvaluationRoundStatus::Open || $round->method !== EvaluationRoundMethod::Individual
                || $round->is_final || $competition->results_published_at
                || $competition->evaluationRounds()->where(fn ($query) => $query->where('is_final', true)->orWhere('round_number', '>', 1))->exists()
                || app(CompetitionPhaseService::class)->phase($competition) !== CompetitionOperationalPhase::EvaluationOpen) {
                throw ValidationException::withMessages(['scores' => __('juri.evaluation.errors.round_closed')]);
            }
            $data = $this->data($assignment, $round, $competition);
            if ($data['finalized']) {
                throw ValidationException::withMessages(['scores' => __('juri.evaluation.errors.finalized')]);
            }
            if (! hash_equals($data['evaluationContext'], $context)) {
                throw ValidationException::withMessages(['scores' => __('evaluation_revision.stale')]);
            }
            $criteria = $assignment->category->evaluationCriteria->keyBy('id');
            $photoIds = $data['photos']->pluck('id')->all();
            foreach ($scores as $photoId => $criterionScores) {
                if (! in_array($photoId, $photoIds, true) || ! is_array($criterionScores)) {
                    throw ValidationException::withMessages(['scores' => __('juri.evaluation.errors.invalid_score')]);
                }
                foreach ($criterionScores as $criterionId => $score) {
                    $criterion = $criteria->get($criterionId);
                    if (! $criterion || filter_var($score, FILTER_VALIDATE_INT) === false || $score < $criterion->min_score || $score > $criterion->max_score) {
                        throw ValidationException::withMessages(['scores' => __('juri.evaluation.errors.invalid_score')]);
                    }
                    JuryScore::updateOrCreate(['competition_evaluation_round_id' => $round->id, 'juror_assignment_id' => $assignment->id,
                        'submission_photo_id' => $photoId, 'criterion_assignment_id' => $criterionId], ['score' => (int) $score]);
                }
            }
            if ($finalize) {
                $stored = JuryScore::where('competition_evaluation_round_id', $round->id)->where('juror_assignment_id', $assignment->id)
                    ->whereIn('submission_photo_id', $photoIds)->whereIn('criterion_assignment_id', $criteria->keys())->get();
                if (! count($photoIds) || ! $criteria->count() || $stored->count() !== count($photoIds) * $criteria->count()
                    || $stored->contains(fn ($score) => $score->score < $criteria[$score->criterion_assignment_id]->min_score || $score->score > $criteria[$score->criterion_assignment_id]->max_score)) {
                    throw ValidationException::withMessages(['scores' => __('juri.evaluation.errors.incomplete')]);
                }
                JuryEvaluationSubmission::create(['competition_evaluation_round_id' => $round->id, 'juror_assignment_id' => $assignment->id, 'finalized_at' => now()]);
                JuryScore::whereKey($stored->modelKeys())->update(['submitted_at' => now()]);
                app(CompetitionAuditService::class)->record($competition, 'jury_evaluation_finalized', $assignment->juror,
                    changes: ['round_id' => $round->id, 'category_id' => $assignment->competition_category_id, 'assignment_id' => $assignment->id, 'evaluation_context' => $context]);
            }
            $assignment->increment('evaluation_version');
        });
    }
}
