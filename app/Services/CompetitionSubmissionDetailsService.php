<?php

namespace App\Services;

use App\Models\CompetitionEntry;
use App\Models\CompetitionSubmission;
use App\Models\User;
use App\Support\Photo\SubmissionDeclarations;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CompetitionSubmissionDetailsService
{
    public function __construct(private readonly CompetitionEntryMutationPolicy $mutations, private readonly CompetitionSubmissionPhotoService $photos) {}

    public function update(CompetitionSubmission $submission, User $actor, int $version, array $payload): void
    {
        $competitionId = $submission->entry->competition_id;
        DB::transaction(function () use ($competitionId, $submission, $actor, $version, $payload) {
            CompetitionMutationLock::acquire($competitionId);
            $entry = CompetitionEntry::whereKey($submission->competition_entry_id)->lockForUpdate()->firstOrFail();
            abort_unless($entry->user_id === $actor->id, 404);
            $submission = $entry->submissions()->whereKey($submission->id)->lockForUpdate()->firstOrFail();
            $submission->setRelation('entry', $entry);
            if (! $this->mutations->allows($submission)) {
                throw ValidationException::withMessages(['details' => __('uye.competitions.errors.submission_locked')]);
            }
            if ($submission->details_version !== $version) {
                throw ValidationException::withMessages(['details' => __('declarations.stale')]);
            }
            $photos = $submission->activePhotos()->lockForUpdate()->get();
            $validated = SubmissionDeclarations::validate($submission->category, $payload, ! $submission->status->isEditable());
            $ids = collect($validated['photos'])->pluck('id')->sort()->values()->all();
            if ($ids !== $photos->pluck('id')->sort()->values()->all()) {
                throw ValidationException::withMessages(['details' => __('declarations.stale')]);
            }
            $changed = false;
            foreach ($validated['photos'] as $item) {
                $photo = $photos->firstWhere('id', $item['id']);
                $photo->declaration = collect($item)->only(['title', 'location', 'taken_on', 'story'])->all();
                if ($submission->category->requiresPhotoOrder()) {
                    $photo->sort_order = (int) $item['position'] * 10;
                }
                $changed = $photo->isDirty() || $changed;
                $photo->save();
            }
            $submission->category_story = $validated['category_story'] ?? null;
            $changed = $submission->isDirty('category_story') || $changed;
            if (! $changed) {
                return;
            }
            $submission->details_version++;
            $submission->save();
            if (! $submission->status->isEditable()) {
                $this->photos->reopenForDetailsChange($submission);
            }
            $entry->events()->create(['event' => 'submission_details_updated', 'actor_type' => $actor::class, 'actor_id' => $actor->id,
                'context' => ['submission_id' => $submission->id, 'details_version' => $submission->details_version]]);
        });
    }
}
