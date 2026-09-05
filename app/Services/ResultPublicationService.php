<?php

namespace App\Services;

use App\Models\Competition;
use App\Models\CompetitionCategoryJurorAssignment;
use App\Models\CompetitionEvaluationRound;
use App\Models\CompetitionResultPublication;
use App\Models\CompetitionSubmissionPhoto;
use App\Models\EysUser;
use App\Notifications\Juri\CompetitionResultsPublishedNotification as JuryResultsPublishedNotification;
use App\Notifications\Uye\CompetitionResultsPublishedNotification as MemberResultsPublishedNotification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ResultPublicationService
{
    public function create(Competition $competition, CompetitionEvaluationRound $round, EysUser $actor, ?string $note = null, ?Carbon $publishedAt = null): CompetitionResultPublication
    {
        return DB::transaction(function () use ($competition, $round, $actor, $note, $publishedAt) {
            $competition = CompetitionMutationLock::acquire($competition->id);
            $round = $round->fresh();
            abort_unless($round->competition_id === $competition->id, 404);
            $snapshot = app(ResultSnapshotBuilder::class)->build($competition, $round, true);
            $publicPhotoIds = collect($snapshot['results'])->filter(fn ($row) => ! empty($row['awards']))->pluck('photo_id');
            $owners = collect($snapshot['member_entries'])->flatMap(fn ($entry) => collect($entry['photos'])->mapWithKeys(fn ($photo) => [$photo['photo_id'] => $entry['user_id']]));
            $publication = $competition->resultPublications()->create([
                'version' => $competition->results_publication_version + 1,
                'snapshot' => $snapshot,
                'snapshot_version' => 3,
                'search_text' => implode(' ', $snapshot['competition']['name']).' '.$snapshot['competition']['institution'],
                'publication_note' => $note,
                'published_by' => $actor->id,
                'published_at' => $publishedAt ?? now(),
            ]);
            $disk = Storage::disk('local');
            DB::afterRollBack(fn () => $disk->deleteDirectory('result-publications/'.$publication->id));
            foreach (CompetitionSubmissionPhoto::whereIn('id', $owners->keys())->get() as $photo) {
                if (! $photo->jury_sanitized_at || ! $photo->jury_path || ! $disk->exists($photo->jury_path)) {
                    throw ValidationException::withMessages(['results' => __('result.archive_image_missing', ['code' => $photo->workCode()])]);
                }
                $path = 'result-publications/'.$publication->id.'/'.$photo->id;
                $stream = $disk->readStream($photo->jury_path);
                try {
                    if (! is_resource($stream) || ! $disk->put($path, $stream)) {
                        throw new \RuntimeException('Could not freeze publication image.');
                    }
                } finally {
                    if (is_resource($stream)) {
                        fclose($stream);
                    }
                }
                $mime = $disk->mimeType($path);
                if (! in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
                    throw ValidationException::withMessages(['results' => __('result.archive_image_missing', ['code' => $photo->workCode()])]);
                }
                $publication->assets()->create([
                    'source_photo_id' => $photo->id,
                    'owner_user_id' => $owners->get($photo->id), 'is_public' => $publicPhotoIds->contains($photo->id),
                    'disk_path' => $path,
                    'sha256' => hash_file('sha256', $disk->path($path)),
                    'mime_type' => $mime,
                    'file_size_bytes' => $disk->size($path),
                ]);
            }

            return $publication;
        });
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
        $competition->resultPublications()->where('version', $competition->results_publication_version)->whereNull('withdrawn_at')->first()?->update([
            'withdrawn_at' => now(),
            'correction_note' => $reason,
        ]);
    }
}
