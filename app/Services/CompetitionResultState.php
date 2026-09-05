<?php

namespace App\Services;

use App\Models\Competition;
use App\Models\CompetitionCategoryJurorAssignment;
use App\Models\CompetitionEvaluationRound;
use App\Models\JuryEvaluationSubmission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CompetitionResultState
{
    private function digest(array $header, array $queries): string
    {
        $hash = hash_init('sha256');
        hash_update($hash, json_encode($header, JSON_THROW_ON_ERROR));
        foreach ($queries as $name => $query) {
            hash_update($hash, "\n".$name."\n");
            foreach ($query->orderBy('id')->lazyById(500, 'id') as $row) {
                hash_update($hash, json_encode($row, JSON_THROW_ON_ERROR)."\n");
            }
        }

        return hash_final($hash);
    }

    private function categoryIds(string $competitionId)
    {
        return DB::table('competition_categories')->where('competition_id', $competitionId)->select('id');
    }

    private function submissions(string $competitionId)
    {
        return DB::table('competition_submissions')->whereIn('competition_entry_id', DB::table('competition_entries')->where('competition_id', $competitionId)->select('id'));
    }

    public function inputHash(CompetitionEvaluationRound $round): string
    {
        $id = $round->competition_id;
        $queries = [
            'category_names' => DB::table('competition_category_translations')->whereIn('competition_category_id', $this->categoryIds($id))->select('id', 'competition_category_id', 'locale', 'name'),
            'submissions' => $this->submissions($id)->select('id', 'competition_category_id', 'status', 'details_version', 'category_story'),
            'photos' => DB::table('competition_submission_photos')->whereIn('competition_submission_id', $this->submissions($id)->select('id'))
                ->select('id', 'competition_submission_id', 'sha256', 'withdrawn_at', 'sort_order', 'declaration'),
        ];
        if ($round->method->value === 'individual') {
            $queries += [
                'criteria' => DB::table('competition_category_evaluation_criteria')->whereIn('competition_category_id', $this->categoryIds($id))
                    ->select('id', 'competition_category_id', 'evaluation_criterion_id', 'min_score', 'max_score', 'weight'),
                'jurors' => DB::table('competition_category_juror_assignments')->whereIn('competition_category_id', $this->categoryIds($id))->select('id', 'competition_category_id', 'juror_id', 'evaluation_version'),
                'scores' => DB::table('jury_scores')->where('competition_evaluation_round_id', $round->id)->select('id', 'juror_assignment_id', 'submission_photo_id', 'criterion_assignment_id', 'score', 'submitted_at'),
                'completions' => DB::table('jury_evaluation_submissions')->where('competition_evaluation_round_id', $round->id)->select('id', 'juror_assignment_id', 'finalized_at'),
            ];
        } else {
            $queries['decisions'] = DB::table('competition_committee_decisions')->where('competition_evaluation_round_id', $round->id)->select('id', 'submission_photo_id', 'decision', 'score', 'rank', 'note', 'decided_by', 'decided_at');
        }

        return $this->digest([$round->id, $round->method->value], $queries);
    }

    private function results(CompetitionEvaluationRound $round)
    {
        return DB::table('competition_photo_results')->where('competition_evaluation_round_id', $round->id)
            ->select('id', 'submission_photo_id', 'total_score', 'average_score', 'score_count', 'rank');
    }

    private function awards(CompetitionEvaluationRound $round): array
    {
        $ids = $this->categoryIds($round->competition_id);
        $definitions = DB::table('competition_category_awards')->whereIn('competition_category_id', $ids);
        $references = (clone $definitions)->select('award_reference_id');

        return [
            'definitions' => (clone $definitions)->select('id', 'competition_category_id', 'award_reference_id', 'quantity', 'sort_order'),
            'definition_names' => DB::table('competition_category_award_translations')->whereIn('competition_category_award_id', (clone $definitions)->select('id'))->select('id', 'competition_category_award_id', 'locale', 'special_award_text', 'material_award'),
            'references' => DB::table('award_references')->whereIn('id', $references)->select('id', 'code', 'kind', 'status', 'version', 'deleted_at'),
            'reference_names' => DB::table('award_reference_translations')->whereIn('award_reference_id', $references)->select('id', 'award_reference_id', 'locale', 'name', 'description'),
            'assigned' => DB::table('competition_result_awards')->whereIn('competition_photo_result_id', DB::table('competition_photo_results')->where('competition_evaluation_round_id', $round->id)->select('id'))
                ->select('id', 'competition_photo_result_id', 'competition_category_award_id', 'slot_number'),
        ];
    }

    public function awardHash(CompetitionEvaluationRound $round): string
    {
        return $this->digest([$this->inputHash($round)], ['results' => $this->results($round)] + $this->awards($round));
    }

    public function resultHash(CompetitionEvaluationRound $round): string
    {
        return $this->digest([$this->inputHash($round)], ['results' => $this->results($round)]);
    }

    public function completionCounts(Competition $competition, ?CompetitionEvaluationRound $round): array
    {
        $expectedIds = CompetitionCategoryJurorAssignment::query()
            ->whereHas('category', fn ($query) => $query->where('competition_id', $competition->id)->whereHas('submissions', fn ($submissions) => $submissions->where('status', 'approved')))
            ->whereNotNull('juror_id')->pluck('id');
        $completed = $round ? JuryEvaluationSubmission::where('competition_evaluation_round_id', $round->id)->whereIn('juror_assignment_id', $expectedIds)->count() : 0;

        return [$expectedIds->count(), $completed];
    }

    public function isFresh(CompetitionEvaluationRound $round): bool
    {
        return $round->results_state_hash !== null && hash_equals($round->results_state_hash, $this->resultHash($round));
    }

    public function awardsFresh(CompetitionEvaluationRound $round): bool
    {
        return $round->awards_context_hash !== null && hash_equals($round->awards_context_hash, $this->awardHash($round));
    }

    public function context(Competition $competition): string
    {
        return DB::transaction(function () use ($competition) {
            $competition = CompetitionMutationLock::acquire($competition->id);
            $rounds = $competition->evaluationRounds()->orderBy('id')->get();
            $state = [$competition->only(['id', 'results_edit_version', 'results_publication_version', 'results_published_at', 'publication_state', 'status'])];
            foreach ($rounds as $round) {
                $state[] = [$round->only(['id', 'round_number', 'method', 'status', 'is_final', 'results_state_hash', 'awards_context_hash']),
                    $this->awardHash($round), $round->jurySession?->only(['id', 'status', 'version'])];
            }

            return hash_hmac('sha256', json_encode($state, JSON_THROW_ON_ERROR), config('app.key'));
        });
    }

    public function assertCurrent(Request $request, Competition $competition): void
    {
        $data = $request->validate(['result_context' => ['required', 'string', 'size:64']]);
        if (! hash_equals($this->context($competition), $data['result_context'])) {
            throw ValidationException::withMessages(['results' => __('result_selection.stale')]);
        }
    }

    public function assertFresh(CompetitionEvaluationRound $round): void
    {
        if (! $this->isFresh($round)) {
            throw ValidationException::withMessages(['results' => __('result_selection.recalculate')]);
        }
    }
}
