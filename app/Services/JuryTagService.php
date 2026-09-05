<?php

namespace App\Services;

use App\Enums\CompetitionOperationalPhase;
use App\Enums\CompetitionSubmissionStatus;
use App\Models\Competition;
use App\Models\CompetitionCategory;
use App\Models\CompetitionCategoryJurorAssignment;
use App\Models\CompetitionSubmissionPhoto;
use App\Models\Juri;
use App\Models\JuryTag;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class JuryTagService
{
    public function authorize(Juri $juror, Competition $competition, CompetitionCategory $category, bool $lock = false): void
    {
        abort_unless($category->competition_id === $competition->id, 404);
        $assignment = CompetitionCategoryJurorAssignment::where('juror_id', $juror->id)->where('competition_category_id', $category->id);
        if ($lock) {
            $assignment->lockForUpdate();
        }
        // Lock before policy reads establish a REPEATABLE READ snapshot. The next
        // writer must see tags and photo links committed by the previous writer.
        $assignment->firstOrFail();
        Gate::forUser($juror)->authorize('evaluate', $category);
        abort_unless(in_array(app(CompetitionPhaseService::class)->phase($competition), [
            CompetitionOperationalPhase::EvaluationOpen, CompetitionOperationalPhase::EvaluationClosed, CompetitionOperationalPhase::ResultsPublished,
        ], true), 404);
    }

    public function listing(Juri $juror, Competition $competition, CompetitionCategory $category): Collection
    {
        $this->authorize($juror, $competition, $category);

        return JuryTag::where('juror_id', $juror->id)->where('competition_category_id', $category->id)
            ->with(['photos' => fn ($query) => $query->select('competition_submission_photos.id')->whereNull('withdrawn_at')->whereHas('submission', fn ($submission) => $submission->where('competition_category_id', $category->id)->where('status', CompetitionSubmissionStatus::Approved))])
            ->orderBy('created_at')->orderBy('id')->get()->map(fn ($tag) => $this->present($tag));
    }

    public function create(Juri $juror, Competition $competition, CompetitionCategory $category, array $data): array
    {
        return DB::transaction(function () use ($juror, $competition, $category, $data) {
            $this->authorize($juror, $competition, $category, true);
            $name = trim($data['name']);
            $tag = JuryTag::query()->lockForUpdate()->firstOrCreate([
                'juror_id' => $juror->id, 'competition_category_id' => $category->id,
                'name_key' => hash('sha256', mb_strtolower($name)),
            ], ['name' => $name, 'color' => strtolower($data['color'])]);

            return $this->present($tag);
        }, 3);
    }

    public function delete(Juri $juror, Competition $competition, CompetitionCategory $category, string $tagId): void
    {
        DB::transaction(function () use ($juror, $competition, $category, $tagId) {
            $this->authorize($juror, $competition, $category, true);
            $this->owned($juror, $category, $tagId)->delete();
        }, 3);
    }

    public function assign(Juri $juror, Competition $competition, CompetitionCategory $category, string $tagId, string $photoId, bool $attach): array
    {
        return DB::transaction(function () use ($juror, $competition, $category, $tagId, $photoId, $attach) {
            $this->authorize($juror, $competition, $category, true);
            $tag = $this->owned($juror, $category, $tagId);
            $photo = CompetitionSubmissionPhoto::whereKey($photoId)
                ->whereHas('submission', fn ($query) => $query->where('competition_category_id', $category->id))
                ->lockForUpdate()->firstOrFail();
            if ($attach) {
                abort_unless(! $photo->withdrawn_at && $photo->submission->status === CompetitionSubmissionStatus::Approved, 404);
                $tag->photos()->syncWithoutDetaching([$photo->id]);
            } else {
                $tag->photos()->detach($photo->id);
            }

            return $this->present($tag);
        }, 3);
    }

    private function owned(Juri $juror, CompetitionCategory $category, string $id): JuryTag
    {
        return JuryTag::whereKey($id)->where('juror_id', $juror->id)->where('competition_category_id', $category->id)->lockForUpdate()->firstOrFail();
    }

    private function present(JuryTag $tag): array
    {
        $photos = $tag->relationLoaded('photos') ? $tag->photos : $tag->photos()->select('competition_submission_photos.id')->whereNull('withdrawn_at')
            ->whereHas('submission', fn ($query) => $query->where('competition_category_id', $tag->competition_category_id)->where('status', CompetitionSubmissionStatus::Approved))->get();

        return ['id' => $tag->id, 'name' => $tag->name, 'color' => $tag->color, 'photo_ids' => $photos->pluck('id')->values()->all()];
    }
}
