<?php

namespace App\Http\Controllers\Juri;

use App\Enums\CompetitionOperationalPhase;
use App\Enums\EvaluationRoundStatus;
use App\Http\Controllers\Controller;
use App\Models\Competition;
use App\Models\CompetitionCategory;
use App\Services\CompetitionPhaseService;
use App\Services\JuryEvaluationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class EvaluationController extends Controller
{
    public function show(Competition $competition, CompetitionCategory $category, CompetitionPhaseService $phases, JuryEvaluationService $service): View
    {
        abort_unless($category->competition_id === $competition->id, 404);
        $phase = $phases->phase($competition);
        abort_unless(in_array($phase, [CompetitionOperationalPhase::EvaluationOpen, CompetitionOperationalPhase::EvaluationClosed, CompetitionOperationalPhase::ResultsPublished], true), 404);
        $assignment = $service->assignmentFor(Auth::guard('juri')->user(), $competition, $category->id);
        $round = $service->roundFor($competition);
        if ($phase !== CompetitionOperationalPhase::EvaluationOpen && $round->status === EvaluationRoundStatus::Open) {
            $round->update(['status' => EvaluationRoundStatus::Closed]);
        }

        return view('juri.evaluations.show', array_merge(
            ['competition' => $competition, 'assignment' => $assignment, 'round' => $round, 'phase' => $phase],
            $service->evaluationData($assignment, $round),
        ));
    }

    public function save(Request $request, Competition $competition, CompetitionCategory $category, CompetitionPhaseService $phases, JuryEvaluationService $service): RedirectResponse
    {
        abort_unless($category->competition_id === $competition->id && $phases->phase($competition) === CompetitionOperationalPhase::EvaluationOpen, 422);
        $assignment = $service->assignmentFor(Auth::guard('juri')->user(), $competition, $category->id);
        $round = $service->roundFor($competition);
        $validated = $request->validate(['scores' => ['nullable', 'array'], 'scores.*' => ['array'], 'scores.*.*' => ['nullable', 'integer']]);
        $service->save($assignment, $round, $validated['scores'] ?? []);

        return back()->with('status', __('juri.evaluation.saved'));
    }

    public function finalize(Request $request, Competition $competition, CompetitionCategory $category, CompetitionPhaseService $phases, JuryEvaluationService $service): RedirectResponse
    {
        abort_unless($category->competition_id === $competition->id && $phases->phase($competition) === CompetitionOperationalPhase::EvaluationOpen, 422);
        $assignment = $service->assignmentFor(Auth::guard('juri')->user(), $competition, $category->id);
        $round = $service->roundFor($competition);
        $validated = $request->validate(['scores' => ['nullable', 'array'], 'scores.*' => ['array'], 'scores.*.*' => ['nullable', 'integer']]);
        $service->save($assignment, $round, $validated['scores'] ?? []);
        $service->finalize($assignment, $round);

        return back()->with('status', __('juri.evaluation.finalized'));
    }
}
