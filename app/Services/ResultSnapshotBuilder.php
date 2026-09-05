<?php

namespace App\Services;

use App\Models\Competition;
use App\Models\CompetitionEvaluationRound;

class ResultSnapshotBuilder
{
    public function build(Competition $competition, CompetitionEvaluationRound $round, bool $includeMemberRecords = false): array
    {
        $competition->load(['translations', 'institution', 'competitionType.translations', 'categories.translations']);
        $round->load(['results.photo.submission.category.translations', 'results.photo.submission.entry.user', 'results.awards.categoryAward.translations', 'results.awards.categoryAward.awardReference.translations']);

        $snapshot = [
            'competition' => [
                'id' => $competition->id,
                'name' => $competition->translations->pluck('name', 'locale')->all(),
                'subject' => $competition->translations->pluck('subject', 'locale')->all(),
                'institution' => $competition->institution?->name,
                'type' => $competition->competitionType?->translations->pluck('name', 'locale')->all() ?? [],
            ],
            'categories' => $competition->categories->map(fn ($category) => ['id' => $category->id, 'photos_grouped' => $category->photos_grouped, 'name' => $category->translations->pluck('name', 'locale')->all()])->values()->all(),
            'participant_count' => $competition->entries()->whereNotNull('submitted_at')->count(),
            'photo_count' => $competition->entries()->whereNotNull('competition_entries.submitted_at')
                ->join('competition_submissions', 'competition_submissions.competition_entry_id', '=', 'competition_entries.id')
                ->join('competition_submission_photos', 'competition_submission_photos.competition_submission_id', '=', 'competition_submissions.id')
                ->whereNull('competition_submission_photos.withdrawn_at')->count(),
            'round' => ['id' => $round->id, 'number' => $round->round_number, 'name' => $round->name],
            'results' => $round->results->filter(fn ($result) => ! $result->photo->withdrawn_at && $result->photo->submission->status->value === 'approved')->map(fn ($result) => [
                'photo_id' => $result->submission_photo_id,
                'work_code' => $result->photo->workCode(),
                'series_code' => $result->photo->submission->category->photos_grouped ? $result->photo->submission->seriesCode() : null,
                'sort_order' => $result->photo->sort_order,
                'category_id' => $result->photo->submission->competition_category_id,
                'category' => $result->photo->submission->category->translations->pluck('name', 'locale')->all(),
                'participant_id' => $result->photo->submission->entry->user_id,
                'participant' => trim($result->photo->submission->entry->user->first_name.' '.$result->photo->submission->entry->user->last_name),
                'rank' => $result->rank,
                'average_score' => $result->average_score,
                'total_score' => $result->total_score, 'score_count' => $result->score_count,
                'awards' => $result->awards->map(fn ($award) => [
                    'slot' => $award->slot_number,
                    'name' => $award->categoryAward->awardReference?->translations->pluck('name', 'locale')->all()
                        ?: $award->categoryAward->translations->pluck('special_award_text', 'locale')->all(),
                    'material_award' => $award->categoryAward->translations->pluck('material_award', 'locale')->all(),
                ])->values()->all(),
            ])->values()->all(),
        ];
        if ($includeMemberRecords) {
            $cards = app(MemberScorecardService::class)->captureForCompetition($competition);
            $entries = $competition->entries()->whereHas('submissions', fn ($query) => $query->where('status', 'approved'))
                ->with(['submissions' => fn ($query) => $query->where('status', 'approved'), 'submissions.category.translations', 'submissions.photos.captureDevice.translations'])->get();
            $snapshot['member_entries'] = $entries->map(fn ($entry) => [
                'entry_id' => $entry->id, 'user_id' => $entry->user_id,
                'photos' => $entry->submissions->flatMap(fn ($submission) => $submission->photos->whereNull('withdrawn_at')->sortBy('sort_order')->map(fn ($photo) => [
                    'photo_id' => $photo->id, 'work_code' => $photo->workCode(), 'category_id' => $submission->competition_category_id,
                    'category' => $submission->category->translations->pluck('name', 'locale')->all(),
                    'series_code' => $submission->category->photos_grouped ? $submission->seriesCode() : null,
                    'series_id' => $submission->category->photos_grouped ? $submission->id : null,
                    'category_story' => $submission->category_story, 'sort_order' => $photo->sort_order,
                    'declaration' => $photo->declarationData(),
                    'capture_device' => $photo->captureDevice?->translations->pluck('name', 'locale')->all() ?? [],
                    'scorecards' => $cards[$photo->id] ?? [],
                ]))->values()->all(),
            ])->values()->all();
        }

        return $snapshot;
    }
}
