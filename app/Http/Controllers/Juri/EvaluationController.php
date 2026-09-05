<?php

namespace App\Http\Controllers\Juri;

use App\Enums\CompetitionOperationalPhase;
use App\Http\Controllers\Controller;
use App\Models\Competition;
use App\Models\CompetitionCategory;
use App\Services\CompetitionPhaseService;
use App\Services\JuryEvaluationService;
use App\Services\JuryTagService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class EvaluationController extends Controller
{
    public function show(Request $request, Competition $competition, CompetitionCategory $category, CompetitionPhaseService $phases, JuryEvaluationService $service, JuryTagService $tags): View
    {
        abort_unless($category->competition_id === $competition->id, 404);
        Gate::forUser(Auth::guard('juri')->user())->authorize('evaluate', $category);
        $phase = $phases->phase($competition);
        abort_unless(in_array($phase, [CompetitionOperationalPhase::EvaluationOpen, CompetitionOperationalPhase::EvaluationClosed, CompetitionOperationalPhase::ResultsPublished], true), 404);
        $assignment = $service->assignmentFor(Auth::guard('juri')->user(), $competition, $category->id);
        $round = $service->roundFor($competition);

        $privateTags = $tags->listing(Auth::guard('juri')->user(), $competition, $category);
        $selectedTag = $request->validate(['tag' => ['nullable', 'uuid']])['tag'] ?? '';
        abort_if($selectedTag !== '' && ! $privateTags->contains('id', $selectedTag), 404);

        return view('juri.evaluations.show', array_merge(
            ['competition' => $competition, 'assignment' => $assignment, 'round' => $round, 'phase' => $phase, 'privateTags' => $privateTags, 'selectedTag' => $selectedTag],
            $service->evaluationData($assignment, $round),
        ));
    }

    public function save(Request $request, Competition $competition, CompetitionCategory $category, CompetitionPhaseService $phases, JuryEvaluationService $service): RedirectResponse
    {
        abort_unless($category->competition_id === $competition->id && $phases->phase($competition) === CompetitionOperationalPhase::EvaluationOpen, 422);
        Gate::forUser(Auth::guard('juri')->user())->authorize('evaluate', $category);
        $assignment = $service->assignmentFor(Auth::guard('juri')->user(), $competition, $category->id);
        $round = $service->roundFor($competition);
        $validated = $request->validate(['evaluation_context' => ['required', 'string', 'size:64'], 'scores' => ['nullable', 'array'], 'scores.*' => ['array'], 'scores.*.*' => ['nullable', 'integer']]);
        $service->save($assignment, $round, $validated['scores'] ?? [], $validated['evaluation_context']);

        return back()->with('status', __('juri.evaluation.saved'));
    }

    public function finalize(Request $request, Competition $competition, CompetitionCategory $category, CompetitionPhaseService $phases, JuryEvaluationService $service): RedirectResponse
    {
        abort_unless($category->competition_id === $competition->id && $phases->phase($competition) === CompetitionOperationalPhase::EvaluationOpen, 422);
        Gate::forUser(Auth::guard('juri')->user())->authorize('evaluate', $category);
        $assignment = $service->assignmentFor(Auth::guard('juri')->user(), $competition, $category->id);
        $round = $service->roundFor($competition);
        $validated = $request->validate(['evaluation_context' => ['required', 'string', 'size:64'], 'scores' => ['nullable', 'array'], 'scores.*' => ['array'], 'scores.*.*' => ['nullable', 'integer']]);
        $service->save($assignment, $round, $validated['scores'] ?? [], $validated['evaluation_context'], true);

        return back()->with('status', __('juri.evaluation.finalized'));
    }
}
