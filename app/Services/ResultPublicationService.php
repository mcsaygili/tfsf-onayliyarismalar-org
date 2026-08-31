<?php

namespace App\Services;

use App\Models\Competition;
use App\Models\CompetitionCategoryJurorAssignment;
use App\Models\CompetitionEvaluationRound;
use App\Models\CompetitionResultPublication;
use App\Models\EysUser;
use App\Notifications\Juri\CompetitionResultsPublishedNotification as JuryResultsPublishedNotification;
use App\Notifications\Uye\CompetitionResultsPublishedNotification as MemberResultsPublishedNotification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;

class ResultPublicationService
{
    public function create(Competition $competition, CompetitionEvaluationRound $round, EysUser $actor, ?string $note = null, ?Carbon $publishedAt = null): CompetitionResultPublication
    {
        $round->load([
            'results.photo.submission.category.translations',
            'results.photo.submission.entry.user',
            'results.awards.categoryAward.translations',
            'results.awards.categoryAward.awardReference.translations',
        ]);
        $competition->loadMissing(['translations', 'institution']);
        $version = $competition->results_publication_version + 1;
        $snapshot = [
            'competition' => [
                'id' => $competition->id,
                'name' => $competition->translations->mapWithKeys(fn ($translation) => [$translation->locale => $translation->name])->all(),
                'institution' => $competition->institution?->name,
            ],
            'round' => ['id' => $round->id, 'number' => $round->round_number, 'name' => $round->name],
            'results' => $round->results->filter(fn ($result) => ! $result->photo->withdrawn_at)->map(fn ($result) => [
                'photo_id' => $result->submission_photo_id,
                'category_id' => $result->photo->submission->competition_category_id,
                'category' => $result->photo->submission->category->translations->mapWithKeys(fn ($translation) => [$translation->locale => $translation->name])->all(),
                'participant' => trim($result->photo->submission->entry->user->first_name.' '.$result->photo->submission->entry->user->last_name),
                'rank' => $result->rank,
                'average_score' => $result->average_score,
                'awards' => $result->awards->map(fn ($award) => [
                    'slot' => $award->slot_number,
                    'name' => $award->categoryAward->awardReference?->translations->mapWithKeys(fn ($translation) => [$translation->locale => $translation->name])->all()
                        ?: $award->categoryAward->translations->mapWithKeys(fn ($translation) => [$translation->locale => $translation->special_award_text])->all(),
                ])->values()->all(),
            ])->values()->all(),
        ];

        return $competition->resultPublications()->create([
            'version' => $version,
            'snapshot' => $snapshot,
            'publication_note' => $note,
            'published_by' => $actor->id,
            'published_at' => $publishedAt ?? now(),
        ]);
    }

    public function notify(CompetitionResultPublication $publication): void
    {
        if ($publication->notified_at || $publication->withdrawn_at || $publication->published_at->isFuture()) {
            return;
        }
        $competition = $publication->competition;
        $members = $competition->entries()->whereNotNull('submitted_at')->with('user')->get()->pluck('user')->filter()->unique('id');
        $jurors = CompetitionCategoryJurorAssignment::query()
            ->whereHas('category', fn ($query) => $query->where('competition_id', $competition->id))
            ->with('juror')->get()->pluck('juror')->filter()->unique('id');
        Notification::sendNow($members, new MemberResultsPublishedNotification($competition, ['database']));
        Notification::sendNow($jurors, new JuryResultsPublishedNotification($competition, ['database']));

        $dispatches = app(NotificationDispatchService::class);
        $members
            ->filter(fn ($member) => data_get($member->preferences, 'results_email', true))
            ->each(fn ($member) => $dispatches->queueResults($member, $competition, false));
        $jurors->each(fn ($juror) => $dispatches->queueResults($juror, $competition, true));
        $publication->update(['notified_at' => now()]);
    }

    public function withdrawCurrent(Competition $competition, string $reason): void
    {
        $competition->resultPublications()->whereNull('withdrawn_at')->latest('version')->first()?->update([
            'withdrawn_at' => now(),
            'correction_note' => $reason,
        ]);
    }
}
