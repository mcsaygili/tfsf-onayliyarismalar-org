<?php

namespace App\Services;

use App\Models\Competition;
use App\Models\CompetitionEntry;
use App\Models\CompetitionResultPublication;
use App\Models\JuryScore;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class MemberScorecardService
{
    public function hasPublicationHistory(Competition $competition): bool
    {
        return $competition->results_publication_version > 0 || $competition->results_published_at !== null || $competition->resultPublications()->exists();
    }

    /** @return array<string, array<int, array<string, mixed>>> */
    public function forEntry(CompetitionEntry $entry): array
    {
        $competition = $entry->competition->fresh();
        if ($this->hasPublicationHistory($competition)) {
            $publication = CompetitionResultPublication::query()->currentPublic()->where('competition_id', $competition->id)->first();
            $record = $publication ? collect($publication->snapshot['member_entries'] ?? [])->first(fn ($record) => $record['entry_id'] === $entry->id && $record['user_id'] === $entry->user_id) : null;
            $cards = collect($record['photos'] ?? [])->mapWithKeys(fn ($photo) => [$photo['photo_id'] => $photo['scorecards'] ?? []])->all();
        } elseif ($competition->newQuery()->whereKey($competition->id)->publiclyVisible()->exists()) {
            $cards = $this->cards($this->query($competition)->where('entries.id', $entry->id));
        } else {
            $cards = [];
        }

        return $this->localized($cards);
    }

    public function captureForCompetition(Competition $competition): array
    {
        return $this->cards($this->query($competition));
    }

    public function localized(array $cards): array
    {
        foreach ($cards as &$rounds) {
            foreach ($rounds as &$round) {
                foreach ($round['scores'] as $index => &$score) {
                    $score['label'] = __('uye.competitions.scorecard_evaluation', ['number' => $index + 1]);
                }
            }
        }

        return $cards;
    }

    private function query(Competition $competition): Builder
    {
        return JuryScore::query()->weightedTotals()
            ->join('competition_submission_photos as photos', 'photos.id', '=', 'jury_scores.submission_photo_id')
            ->join('competition_submissions as submissions', 'submissions.id', '=', 'photos.competition_submission_id')
            ->join('competition_entries as entries', 'entries.id', '=', 'submissions.competition_entry_id')
            ->join('competition_category_juror_assignments as assignments', 'assignments.id', '=', 'jury_scores.juror_assignment_id')
            ->join('competition_evaluation_rounds as rounds', 'rounds.id', '=', 'jury_scores.competition_evaluation_round_id')
            ->where('entries.competition_id', $competition->id)->where('rounds.competition_id', $competition->id)
            ->whereColumn('criteria.competition_category_id', 'submissions.competition_category_id')
            ->whereColumn('assignments.competition_category_id', 'submissions.competition_category_id')
            ->whereNotNull('assignments.juror_id')->whereNull('photos.withdrawn_at')->where('submissions.status', 'approved')
            ->whereNotNull('jury_scores.submitted_at');
    }

    private function cards(Builder $query): array
    {
        return $query->addSelect('jury_scores.submission_photo_id', 'jury_scores.competition_evaluation_round_id', 'jury_scores.juror_assignment_id')
            ->groupBy('jury_scores.submission_photo_id', 'jury_scores.competition_evaluation_round_id', 'jury_scores.juror_assignment_id')
            ->with('round:id,name,round_number')->get()->groupBy('submission_photo_id')
            ->map(fn (Collection $photoScores) => $photoScores->groupBy('competition_evaluation_round_id')
                ->map(function (Collection $roundScores) {
                    $round = $roundScores->first()->round;
                    $scores = $roundScores->map(fn (JuryScore $score) => [
                        'key' => hash('sha256', $score->submission_photo_id.':'.$score->juror_assignment_id),
                        'score' => round((float) $score->average_score, 2),
                    ])->sortBy('key')->values()->map(fn ($row) => ['score' => $row['score']])->all();

                    return [
                        'round_name' => $round->name,
                        'round_number' => $round->round_number,
                        'average' => $roundScores->sum('total_weight') > 0 ? round($roundScores->sum('total_score') / $roundScores->sum('total_weight'), 2) : 0.0,
                        'scores' => $scores,
                    ];
                })->sortBy('round_number')->values()->all())->all();
    }
}
