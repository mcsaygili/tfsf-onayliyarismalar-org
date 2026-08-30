<?php

namespace App\Http\Controllers\Result;

use App\Http\Controllers\Controller;
use App\Models\Competition;
use App\Services\CompetitionResultPresentationService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CompetitionResultController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->input('q'));
        $competitions = Competition::query()
            ->publiclyVisible()
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

    public function show(Competition $competition, CompetitionResultPresentationService $presentation): View
    {
        abort_unless(
            $competition->newQuery()->whereKey($competition->getKey())->publiclyVisible()->exists()
                && $competition->results_published_at?->lte(now()),
            404,
        );

        return view('result.competitions.show', $presentation->forCompetition($competition) + ['preview' => false]);
    }
}
