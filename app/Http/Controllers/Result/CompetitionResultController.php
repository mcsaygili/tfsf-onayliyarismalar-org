<?php

namespace App\Http\Controllers\Result;

use App\Enums\CompetitionStatus;
use App\Http\Controllers\Controller;
use App\Models\Competition;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CompetitionResultController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->input('q'));
        $competitions = Competition::query()
            ->where('status', CompetitionStatus::Approved)
            ->whereNotNull('results_published_at')
            ->where('results_published_at', '<=', now())
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search) {
                $query->whereHas('translations', fn ($translations) => $translations->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('institution', fn ($institution) => $institution->where('name', 'like', "%{$search}%"));
            }))
            ->with(['translations', 'institution', 'competitionType.translations'])
            ->withCount(['categories', 'entries as participant_count' => fn ($query) => $query->whereNotNull('submitted_at')])
            ->orderByDesc('results_published_at')
            ->paginate(18)
            ->withQueryString();

        return view('result.competitions.index', compact('competitions', 'search'));
    }

    public function show(Competition $competition): View
    {
        abort_unless($competition->status === CompetitionStatus::Approved && $competition->results_published_at?->lte(now()), 404);
        $competition->load([
            'translations', 'institution', 'competitionType.translations', 'categories.translations',
            'evaluationRounds.results.photo.submission.category.translations',
            'evaluationRounds.results.photo.submission.entry.user',
            'evaluationRounds.results.awards.categoryAward.translations',
            'evaluationRounds.results.awards.categoryAward.awardReference.translations',
        ]);
        $round = $competition->evaluationRounds->firstWhere('is_final', true)
            ?? $competition->evaluationRounds->sortByDesc('round_number')->first();
        abort_unless($round, 404);
        $results = $round->results->filter(fn ($result) => ! $result->photo->withdrawn_at);
        $awardedResults = $results->filter(fn ($result) => $result->awards->isNotEmpty());
        $participants = $awardedResults->map(fn ($result) => $result->photo->submission->entry->user)->unique('id')->values();

        return view('result.competitions.show', [
            'competition' => $competition,
            'round' => $round,
            'resultsByCategory' => $awardedResults->groupBy(fn ($result) => $result->photo->submission->competition_category_id),
            'participants' => $participants,
            'participantCount' => $competition->entries()->whereNotNull('submitted_at')->count(),
            'photoCount' => $competition->entries()->whereNotNull('competition_entries.submitted_at')->join('competition_submissions', 'competition_submissions.competition_entry_id', '=', 'competition_entries.id')->join('competition_submission_photos', 'competition_submission_photos.competition_submission_id', '=', 'competition_submissions.id')->whereNull('competition_submission_photos.withdrawn_at')->count('competition_submission_photos.id'),
        ]);
    }
}
