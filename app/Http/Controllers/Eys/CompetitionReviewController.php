<?php

namespace App\Http\Controllers\Eys;

use App\Enums\CommitteeDecisionStatus;
use App\Enums\CompetitionPublicationState;
use App\Enums\CompetitionStatus;
use App\Enums\EvaluationRoundMethod;
use App\Enums\EvaluationRoundStatus;
use App\Http\Controllers\Controller;
use App\Models\Competition;
use App\Models\CompetitionEvaluationRound;
use App\Models\CompetitionResultAward;
use App\Models\CompetitionReview;
use App\Models\CompetitionReviewStep;
use App\Models\Temsilci;
use App\Services\CompetitionAuditService;
use App\Services\CompetitionMutationLock;
use App\Services\CompetitionPublicationService;
use App\Services\CompetitionPublicSlugService;
use App\Services\CompetitionReadinessService;
use App\Services\CompetitionResultPresentationService;
use App\Services\CompetitionResultService;
use App\Services\CompetitionResultState;
use App\Services\CompetitionStateMachine;
use App\Services\JurySessionService;
use App\Services\ResultPublicationService;
use App\Support\CompetitionRegulations\CompetitionRegulationCompiler;
use App\Support\CompetitionWizard\CompetitionStep;
use App\Support\CompetitionWizard\CompetitionStepRegistry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CompetitionReviewController extends Controller
{
    public function index(Request $request): View
    {
        $competitions = Competition::query()
            ->with(['institution', 'translations'])
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('eys.competitions.index', [
            'competitions' => $competitions,
            'filter' => ['status' => $request->input('status', '')],
        ]);
    }

    public function show(Competition $competition): View
    {
        return DB::transaction(function () use ($competition) {
            $competition = CompetitionMutationLock::acquire($competition->id);
            $competition->load([
                'institution', 'institutionStaff', 'competitionType.translations',
                'country.translations', 'city.translations', 'participantApprovalProcess.translations',
                'categories.translations', 'categories.ageEligibilityRule.translations',
                'categories.genders.translations', 'categories.memberGroups.translations',
                'categories.captureDevices.translations', 'categories.processingMethods.translations',
                'categories.awards.translations', 'categories.awards.awardReference.translations',
                'categories.jurorAssignments.juror', 'categories.jurorAssignments.invitation',
                'categories.evaluationCriteria.criterion.translations',
                'captureRegions.country.translations', 'captureRegions.city.translations',
                'regulationInputs', 'regulationSnapshots', 'translations', 'statusLogs.actor',
                'reviews.reviewer', 'reviews.steps.addressedBy',
                'evaluationRounds.results.photo.submission.category.translations',
                'evaluationRounds.results.awards.categoryAward.translations',
                'evaluationRounds.results.awards.categoryAward.awardReference.translations',
                'evaluationRounds.evaluationSubmissions',
                'evaluationRounds.committeeDecisions.photo.submission.category.translations',
                'evaluationRounds.jurySession.attendances.juror',
                'monitoringReports.representative', 'resultPublications.publisher',
                'notificationDispatches.lastRetriedBy',
            ]);

            $latestSnapshot = $competition->regulationSnapshots->sortByDesc('version')->first();

            return view('eys.competitions.show', [
                'competition' => $competition,
                'resultContext' => app(CompetitionResultState::class)->context($competition),
                'resultFreshness' => $competition->evaluationRounds->mapWithKeys(fn ($round) => [$round->id => app(CompetitionResultState::class)->isFresh($round)]),
                'awardFreshness' => $competition->evaluationRounds->mapWithKeys(fn ($round) => [$round->id => app(CompetitionResultState::class)->awardsFresh($round)]),
                'steps' => CompetitionStepRegistry::all(),
                'reviewableSteps' => $this->reviewableSteps($competition),
                'latestReview' => $competition->reviews->sortByDesc('round')->first(),
                'pendingJuryAssignments' => app(CompetitionReadinessService::class)->pendingJuryAssignments($competition),
                'compiledRegulation' => $latestSnapshot?->content
                    ?? app(CompetitionRegulationCompiler::class)->preview($competition)['content'],
                'regulationSnapshot' => $latestSnapshot,
                'representatives' => Temsilci::query()->where('status', true)->orderBy('first_name')->orderBy('last_name')->get(),
            ]);
        });
    }

    public function assignRepresentative(Request $request, Competition $competition): RedirectResponse
    {
        $validated = $request->validate(['representative_id' => ['nullable', 'uuid', Rule::exists('representatives', 'id')->where('status', true)]]);
        $competition->forceFill(['representative_id' => $validated['representative_id'] ?? null])->save();

        return back()->with('status', __('eys.competitions.representative_assigned'));
    }

    public function previewResults(Request $request, Competition $competition, CompetitionResultPresentationService $presentation): View
    {
        $input = $request->validate(['version' => ['nullable', 'integer', 'min:1']]);
        $version = $input['version'] ?? ($competition->results_published_at ? $competition->results_publication_version : null);
        if ($version) {
            $publication = $competition->resultPublications()->where('version', $version)->firstOrFail();

            return view('result.competitions.show', $presentation->forPublication($publication, true) + ['preview' => true]);
        }

        return view('result.competitions.show', $presentation->forCompetition($competition) + ['preview' => true]);
    }

    public function aggregateResults(Competition $competition, CompetitionResultService $results, CompetitionAuditService $audit): RedirectResponse
    {
        abort_if($competition->results_published_at, 422);

        $round = $this->resultRound($competition);
        $round->method === EvaluationRoundMethod::Committee
            ? $results->aggregateCommittee($round)
            : $results->aggregate($round);
        $audit->record($competition, 'results_aggregated', Auth::guard('eys')->user(), changes: [
            'round_id' => $round->id,
            'round_number' => $round->round_number,
            'result_count' => $round->results()->count(),
        ]);

        return back()->with('status', __('eys.competitions.results_aggregated'));
    }

    public function createFinalRound(Request $request, Competition $competition, CompetitionAuditService $audit): RedirectResponse
    {
        abort_if($competition->results_published_at, 422);
        app(CompetitionResultState::class)->assertCurrent($request, $competition);
        if ($competition->evaluationRounds()->where(fn ($query) => $query->where('is_final', true)->orWhere('round_number', '>', 1))->exists()) {
            throw ValidationException::withMessages(['results' => __('result_selection.final_exists')]);
        }
        $validated = $request->validate([
            'photo_result_ids' => ['required', 'array', 'min:1'],
            'photo_result_ids.*' => ['required', 'uuid'],
        ]);
        $sourceRound = $competition->evaluationRounds()->where('method', EvaluationRoundMethod::Individual->value)->with('results.photo')->firstOrFail();
        app(CompetitionResultState::class)->assertFresh($sourceRound);
        [$expected, $completed] = app(CompetitionResultState::class)->completionCounts($competition, $sourceRound);
        if ($expected === 0 || $completed < $expected) {
            throw ValidationException::withMessages(['photo_result_ids' => __('eys.competitions.results_incomplete', ['completed' => $completed, 'expected' => $expected])]);
        }
        $photoIds = $sourceRound->results()->whereIn('id', $validated['photo_result_ids'])->pluck('submission_photo_id');
        if ($photoIds->count() !== count(array_unique($validated['photo_result_ids']))) {
            throw ValidationException::withMessages(['photo_result_ids' => __('eys.competitions.final_round_invalid_photos')]);
        }

        $clearedAwards = CompetitionResultAward::query()->whereHas('categoryAward.category', fn ($query) => $query->where('competition_id', $competition->id))
            ->get(['id', 'competition_photo_result_id', 'competition_category_award_id', 'slot_number'])->toArray();
        DB::transaction(function () use ($competition, $photoIds, $sourceRound): void {
            $sourceRound->update(['status' => EvaluationRoundStatus::Finalized, 'finalized_at' => now()]);
            $round = $competition->evaluationRounds()->firstOrCreate(
                ['round_number' => 2],
                ['name' => __('eys.competitions.final_round_name'), 'method' => EvaluationRoundMethod::Committee, 'is_final' => true, 'status' => EvaluationRoundStatus::Open, 'opens_at' => now()],
            );
            foreach ($photoIds as $photoId) {
                $round->committeeDecisions()->firstOrCreate(
                    ['submission_photo_id' => $photoId],
                    ['decision' => CommitteeDecisionStatus::Finalist],
                );
            }
            CompetitionResultAward::query()
                ->whereHas('categoryAward.category', fn ($query) => $query->where('competition_id', $competition->id))
                ->delete();
        });
        $competition->increment('results_edit_version');
        $finalRound = $competition->evaluationRounds()->where('is_final', true)->firstOrFail();
        app(JurySessionService::class)->ensureForRound($finalRound);
        $audit->record($competition, 'final_round_created', Auth::guard('eys')->user(), changes: [
            'source_round_id' => $sourceRound->id,
            'final_round_id' => $finalRound->id,
            'photo_count' => $photoIds->count(),
            'cleared_awards' => $clearedAwards,
        ]);

        return back()->with('status', __('eys.competitions.final_round_created'));
    }

    public function saveFinalRound(Request $request, Competition $competition, CompetitionResultService $results, CompetitionAuditService $audit): RedirectResponse
    {
        abort_if($competition->results_published_at, 422);
        $round = $competition->evaluationRounds()->where('is_final', true)->where('method', EvaluationRoundMethod::Committee->value)->firstOrFail();
        $session = app(JurySessionService::class)->ensureForRound($round);
        if ($session->status !== 'open' || ! $session->hasQuorum()) {
            throw ValidationException::withMessages(['session' => 'Kurul kararları için final oturumu açık olmalı ve yeter sayı sağlanmalıdır.']);
        }
        $validated = $request->validate([
            'session_version' => ['required', 'integer', 'min:0'],
            'decisions' => ['required', 'array'],
            'decisions.*.decision' => ['required', Rule::enum(CommitteeDecisionStatus::class)],
            'decisions.*.score' => ['nullable', 'integer', 'between:3,9'],
            'decisions.*.rank' => ['nullable', 'integer', 'min:1'],
            'decisions.*.note' => ['nullable', 'string', 'max:2000'],
        ]);
        if ((int) $validated['session_version'] !== $session->version) {
            throw ValidationException::withMessages(['session' => __('jury_session.stale')]);
        }
        $decisions = $round->committeeDecisions()->with('photo.submission')->get()->keyBy('id');
        if (array_diff(array_keys($validated['decisions']), $decisions->keys()->all())) {
            throw ValidationException::withMessages(['decisions' => __('eys.competitions.final_round_invalid_photos')]);
        }
        $errors = [];
        $usedRanks = [];
        foreach ($decisions as $id => $existing) {
            $data = $validated['decisions'][$id] ?? ['decision' => $existing->decision->value, 'rank' => $existing->rank];
            $decision = $decisions->get($id);
            if (! $decision) {
                continue;
            }
            if ($data['decision'] === CommitteeDecisionStatus::Selected->value && blank($data['rank'] ?? null)) {
                $errors["decisions.{$id}.rank"] = __('eys.competitions.final_round_rank_required');
            }
            if ($data['decision'] === CommitteeDecisionStatus::Selected->value && filled($data['rank'] ?? null)) {
                $key = $decision->photo->submission->competition_category_id.':'.$data['rank'];
                if (isset($usedRanks[$key])) {
                    $errors["decisions.{$id}.rank"] = __('eys.competitions.final_round_rank_duplicate');
                    $errors["decisions.{$usedRanks[$key]}.rank"] = __('eys.competitions.final_round_rank_duplicate');
                }
                $usedRanks[$key] = $id;
            }
        }
        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        DB::transaction(function () use ($validated, $decisions, $round, $results, $session): void {
            foreach ($validated['decisions'] as $id => $data) {
                if ($decision = $decisions->get($id)) {
                    $decision->update([
                        'decision' => $data['decision'],
                        'score' => $data['score'] ?? null,
                        'rank' => $data['decision'] === CommitteeDecisionStatus::Selected->value ? ($data['rank'] ?? null) : null,
                        'note' => $data['note'] ?? null,
                        'decided_by' => Auth::guard('eys')->id(),
                        'decided_at' => now(),
                    ]);
                }
            }
            $results->aggregateCommittee($round);
            $session->increment('version');
        });
        $audit->record($competition, 'final_round_updated', Auth::guard('eys')->user(), changes: [
            'round_id' => $round->id,
            'decisions' => $round->committeeDecisions()->get(['id', 'decision', 'score', 'rank', 'note'])->toArray(),
        ]);

        return back()->with('status', __('eys.competitions.final_round_saved'));
    }

    public function saveResultAwards(Request $request, Competition $competition, CompetitionAuditService $audit): RedirectResponse
    {
        abort_if($competition->results_published_at, 422);

        app(CompetitionResultState::class)->assertCurrent($request, $competition);
        $validated = $request->validate(['award_assignments' => ['present', 'array'], 'award_assignments.*' => ['array'], 'award_assignments.*.*' => ['nullable', 'uuid']]);
        $round = $competition->evaluationRounds()->when(
            $competition->evaluationRounds()->where('is_final', true)->exists(),
            fn ($query) => $query->where('is_final', true),
            fn ($query) => $query->where('round_number', 1),
        )->with([
            'results.photo.submission',
        ])->firstOrFail();
        app(CompetitionResultState::class)->assertFresh($round);
        $categoryAwards = $competition->categories()->with('awards')->get()
            ->flatMap->awards
            ->keyBy('id');
        $results = $round->results->keyBy('id');
        $assignments = $validated['award_assignments'];
        $expectedSlots = $categoryAwards->flatMap(fn ($award) => collect($award->quantity > 0 ? range(1, $award->quantity) : [])->map(fn ($slot) => $award->id.'.'.$slot))->sort()->values()->all();
        $actualSlots = collect($assignments)->flatMap(fn ($slots, $id) => collect(array_keys($slots))->map(fn ($slot) => $id.'.'.$slot))->sort()->values()->all();
        if ($expectedSlots !== $actualSlots) {
            throw ValidationException::withMessages(['award_assignments' => __('result_selection.award_slots')]);
        }
        $errors = [];
        $rows = [];

        foreach ($categoryAwards as $categoryAward) {
            $usedResultIds = [];
            for ($slot = 1; $slot <= $categoryAward->quantity; $slot++) {
                $resultId = data_get($assignments, $categoryAward->id.'.'.$slot);
                if (blank($resultId)) {
                    continue;
                }

                $result = $results->get($resultId);
                $field = "award_assignments.{$categoryAward->id}.{$slot}";
                if (! $result || $result->photo->submission->competition_category_id !== $categoryAward->competition_category_id) {
                    $errors[$field] = __('eys.competitions.award_result_invalid');

                    continue;
                }
                if (in_array($resultId, $usedResultIds, true)) {
                    $errors[$field] = __('eys.competitions.award_result_duplicate');

                    continue;
                }

                $usedResultIds[] = $resultId;
                $rows[] = [$categoryAward, $slot, $resultId];
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        DB::transaction(function () use ($categoryAwards, $rows): void {
            CompetitionResultAward::query()
                ->whereIn('competition_category_award_id', $categoryAwards->keys())
                ->delete();

            foreach ($rows as [$categoryAward, $slot, $resultId]) {
                CompetitionResultAward::create([
                    'competition_photo_result_id' => $resultId,
                    'competition_category_award_id' => $categoryAward->id,
                    'slot_number' => $slot,
                    'assigned_by' => Auth::guard('eys')->id(),
                ]);
            }
        });
        $round->forceFill(['awards_context_hash' => app(CompetitionResultState::class)->awardHash($round)])->save();
        $competition->increment('results_edit_version');
        $audit->record($competition, 'result_awards_updated', Auth::guard('eys')->user(), changes: [
            'assignment_count' => count($rows),
            'result_ids' => collect($rows)->pluck(2)->values()->all(),
        ]);

        return back()->with('status', __('eys.competitions.result_awards_saved'));
    }

    public function publishResults(Request $request, Competition $competition, CompetitionResultService $results, CompetitionAuditService $audit, ResultPublicationService $publications): RedirectResponse
    {
        if ($competition->results_published_at) {
            return back()->with('status', __('eys.competitions.results_already_published'));
        }
        app(CompetitionResultState::class)->assertCurrent($request, $competition);
        if ($competition->publication_state !== CompetitionPublicationState::Published) {
            return back()->withErrors(['results' => __('eys.competitions.results_require_published_competition')]);
        }

        $individualRound = $competition->evaluationRounds()->where('method', EvaluationRoundMethod::Individual->value)->first();
        [$expected, $completed] = app(CompetitionResultState::class)->completionCounts($competition, $individualRound);
        if ($expected === 0 || $completed < $expected) {
            return back()->withErrors(['results' => __('eys.competitions.results_incomplete', ['completed' => $completed, 'expected' => $expected])]);
        }

        $round = $this->resultRound($competition);
        app(CompetitionResultState::class)->assertFresh($round);
        if ($round->method === EvaluationRoundMethod::Committee) {
            $session = app(JurySessionService::class)->ensureForRound($round);
            if ($session->status !== 'closed') {
                return back()->withErrors(['results' => 'Sonuçlar yayımlanmadan önce final kurul oturumu kapatılmalıdır.']);
            }
        }
        if ($round->method === EvaluationRoundMethod::Committee
            && ($round->committeeDecisions()->where('decision', CommitteeDecisionStatus::Finalist->value)->exists()
                || ! $round->committeeDecisions()->where('decision', CommitteeDecisionStatus::Selected->value)->exists())) {
            return back()->withErrors(['results' => __('eys.competitions.final_round_incomplete')]);
        }

        $requiredAwardSlots = $competition->categories()->withSum('awards as award_slot_count', 'quantity')->get()->sum('award_slot_count');
        $assignedAwardSlots = CompetitionResultAward::query()
            ->whereHas('categoryAward.category', fn ($query) => $query->where('competition_id', $competition->id))
            ->whereHas('result', fn ($query) => $query->where('competition_evaluation_round_id', $round->id))
            ->count();
        if ((int) $assignedAwardSlots !== (int) $requiredAwardSlots) {
            return back()->withErrors(['results' => __('eys.competitions.result_awards_incomplete', ['assigned' => $assignedAwardSlots, 'required' => $requiredAwardSlots])]);
        }

        if ($requiredAwardSlots > 0 && ! app(CompetitionResultState::class)->awardsFresh($round)) {
            throw ValidationException::withMessages(['results' => __('result_selection.review_awards')]);
        }
        $publicationInput = $request->validate(['publication_note' => ['nullable', 'string', 'max:2000'], 'publish_at' => ['nullable', 'date', 'after_or_equal:now']]);
        $publicationNote = $publicationInput['publication_note'] ?? null;
        $publishAt = filled($publicationInput['publish_at'] ?? null) ? Carbon::parse($publicationInput['publish_at']) : now();
        $publication = null;
        DB::transaction(function () use ($competition, $round, $results, $audit, $publications, $publicationNote, $publishAt, &$publication) {
            $round->method === EvaluationRoundMethod::Committee
                ? $results->aggregateCommittee($round)
                : $results->aggregate($round);
            $round->update(['status' => EvaluationRoundStatus::Finalized, 'finalized_at' => now()]);
            $publication = $publications->create($competition, $round, Auth::guard('eys')->user(), $publicationNote, $publishAt);
            $competition->forceFill([
                'results_published_at' => $publishAt,
                'results_publication_version' => $publication->version,
            ])->save();
            $audit->record($competition, 'results_published', Auth::guard('eys')->user(), changes: [
                'round_id' => $round->id,
                'publication_version' => $competition->results_publication_version,
            ]);
        });
        DB::afterCommit(fn () => $publications->notify($publication));

        return back()->with('status', __('eys.competitions.results_published'));
    }

    public function unpublishResults(Request $request, Competition $competition, CompetitionAuditService $audit, ResultPublicationService $publications): RedirectResponse
    {
        abort_unless($competition->results_published_at, 422);
        $validated = $request->validate(['reason' => ['required', 'string', 'min:10', 'max:2000']]);

        DB::transaction(function () use ($competition, $validated, $audit, $publications): void {
            $publishedAt = $competition->results_published_at;
            $publications->withdrawCurrent($competition, $validated['reason']);
            $competition->forceFill(['results_published_at' => null])->save();
            $audit->record($competition, 'results_unpublished_for_correction', Auth::guard('eys')->user(), $validated['reason'], [
                'previous_published_at' => $publishedAt?->toIso8601String(),
                'publication_version' => $competition->results_publication_version,
            ]);
        });

        return back()->with('status', __('eys.competitions.results_unpublished'));
    }

    public function updatePublication(Request $request, Competition $competition, string $action, CompetitionPublicationService $publication): RedirectResponse
    {
        abort_unless(in_array($action, ['suspend', 'resume', 'unpublish', 'cancel'], true), 404);
        $validated = $request->validate([
            'reason' => [$action === 'resume' ? 'nullable' : 'required', 'string', 'min:10', 'max:2000'],
        ]);
        $actor = Auth::guard('eys')->user();

        match ($action) {
            'suspend' => $publication->suspend($competition, $actor, $validated['reason']),
            'resume' => $publication->resume($competition, $actor, $validated['reason'] ?? null),
            'unpublish' => $publication->unpublish($competition, $actor, $validated['reason']),
            'cancel' => $publication->cancel($competition, $actor, $validated['reason']),
        };

        return back()->with('status', __('eys.competitions.publication_action_completed'));
    }

    private function resultRound(Competition $competition): CompetitionEvaluationRound
    {
        return $competition->evaluationRounds()->where('is_final', true)->first()
            ?? $competition->evaluationRounds()->firstOrFail();
    }

    public function start(Competition $competition, CompetitionStateMachine $workflow): RedirectResponse
    {
        abort_unless($competition->status === CompetitionStatus::Submitted, 422);

        DB::transaction(function () use ($competition, $workflow): void {
            $review = $competition->reviews()->create([
                'round' => ((int) $competition->reviews()->max('round')) + 1,
                'reviewer_id' => Auth::guard('eys')->id(),
                'status' => 'in_progress',
                'started_at' => now(),
            ]);

            foreach ($this->reviewableSteps($competition) as $number => $step) {
                $review->steps()->create([
                    'step_number' => $number,
                    'status' => CompetitionReviewStep::STATUS_PENDING,
                ]);
            }

            $workflow->transition(
                $competition,
                CompetitionStatus::UnderReview,
                'review_started',
                Auth::guard('eys')->user(),
                extra: ['reviewed_at' => now(), 'reviewed_by' => Auth::guard('eys')->id()],
            );
        });

        return back()->with('status', __('eys.competitions.review_started'));
    }

    public function save(Request $request, Competition $competition): RedirectResponse
    {
        abort_unless($competition->status === CompetitionStatus::UnderReview, 422);
        $this->persistReviewSteps($request, $this->activeReview($competition));

        return back()->with('status', __('eys.competitions.review_saved'));
    }

    public function approve(Request $request, Competition $competition, CompetitionReadinessService $readiness, CompetitionStateMachine $workflow, CompetitionPublicSlugService $slugs): RedirectResponse
    {
        abort_unless(in_array($competition->status, [CompetitionStatus::UnderReview, CompetitionStatus::WaitingRequirements], true), 422);

        $review = $competition->latestReview();
        if (! $review?->isFullyApproved()) {
            return back()->withErrors(['approval' => __('eys.competitions.review_approval_blocked')]);
        }

        if (! $readiness->allJurorsRegistered($competition)) {
            if ($competition->status === CompetitionStatus::UnderReview) {
                DB::transaction(function () use ($competition, $workflow, $review): void {
                    $review->update(['status' => 'requirements_waiting', 'completed_at' => now()]);
                    $workflow->transition(
                        $competition,
                        CompetitionStatus::WaitingRequirements,
                        'requirements_waiting',
                        Auth::guard('eys')->user(),
                        __('eys.competitions.jury_approval_blocked'),
                    );
                });
            }

            // Waiting for jurors is a successful workflow transition, although
            // the existing UI presents the reason in its approval error area.
            $request->attributes->set('competition_transition_succeeded', true);

            return back()->withErrors(['approval' => __('eys.competitions.jury_approval_blocked')]);
        }

        DB::transaction(function () use ($competition, $workflow, $review, $slugs): void {
            $slugs->ensure($competition);
            $review->update(['status' => 'approved', 'completed_at' => now()]);
            $workflow->transition(
                $competition,
                CompetitionStatus::Approved,
                'approved',
                Auth::guard('eys')->user(),
                extra: [
                    'reviewed_at' => now(),
                    'reviewed_by' => Auth::guard('eys')->id(),
                    'published_at' => now(),
                    'publication_state' => CompetitionPublicationState::Published,
                    'publication_state_changed_at' => now(),
                ],
            );
        });

        return redirect()->route('eys.competitions.index')->with('status', __('eys.competitions.approved'));
    }

    public function reject(Request $request, Competition $competition, CompetitionStateMachine $workflow): RedirectResponse
    {
        abort_unless(in_array($competition->status, [CompetitionStatus::Submitted, CompetitionStatus::UnderReview, CompetitionStatus::WaitingRequirements], true), 422);
        $validated = $request->validate(['message' => ['required', 'string', 'max:2000']]);

        DB::transaction(function () use ($competition, $validated, $workflow): void {
            $latestReview = $competition->latestReview();
            if ($latestReview && in_array($latestReview->status, ['in_progress', 'requirements_waiting'], true)) {
                $latestReview->update(['status' => 'rejected', 'completed_at' => now()]);
            }
            $workflow->transition(
                $competition,
                CompetitionStatus::Rejected,
                'rejected',
                Auth::guard('eys')->user(),
                $validated['message'],
                ['reviewed_at' => now(), 'reviewed_by' => Auth::guard('eys')->id()],
            );
        });

        return redirect()->route('eys.competitions.index')->with('status', __('eys.competitions.rejected'));
    }

    public function requestInfo(Request $request, Competition $competition, CompetitionStateMachine $workflow): RedirectResponse
    {
        abort_unless($competition->status === CompetitionStatus::UnderReview, 422);

        $review = $this->activeReview($competition);
        $this->persistReviewSteps($request, $review);
        $review->refresh()->load('steps');
        $correctionSteps = $review->steps->where('status', CompetitionReviewStep::STATUS_CORRECTION_REQUIRED);

        if ($correctionSteps->isEmpty()) {
            throw ValidationException::withMessages(['review' => __('eys.competitions.correction_required_missing')]);
        }

        $validated = $request->validate(['message' => ['nullable', 'string', 'max:2000']]);
        $message = ($validated['message'] ?? null) ?: __('eys.competitions.correction_summary', ['count' => $correctionSteps->count()]);

        DB::transaction(function () use ($competition, $workflow, $review, $correctionSteps, $message): void {
            $review->update(['status' => 'correction_requested', 'completed_at' => now()]);
            $workflow->transition(
                $competition,
                CompetitionStatus::NeedsInfo,
                'correction_requested',
                Auth::guard('eys')->user(),
                $message,
                [
                    'reviewed_at' => now(),
                    'reviewed_by' => Auth::guard('eys')->id(),
                    'current_step' => $correctionSteps->min('step_number'),
                ],
            );
        });

        return redirect()->route('eys.competitions.index')->with('status', __('eys.competitions.info_requested'));
    }

    private function activeReview(Competition $competition): CompetitionReview
    {
        $review = $competition->latestReview();
        abort_unless($review && $review->status === 'in_progress', 422);

        return $review;
    }

    private function persistReviewSteps(Request $request, CompetitionReview $review): void
    {
        $validated = $request->validate([
            'steps' => ['required', 'array'],
            'steps.*.status' => ['required', Rule::in([
                CompetitionReviewStep::STATUS_PENDING,
                CompetitionReviewStep::STATUS_APPROVED,
                CompetitionReviewStep::STATUS_CORRECTION_REQUIRED,
            ])],
            'steps.*.note' => ['nullable', 'string', 'max:2000'],
        ]);

        $reviewSteps = $review->steps()->get()->keyBy(fn (CompetitionReviewStep $step) => (string) $step->step_number);

        foreach ($validated['steps'] as $number => $decision) {
            $reviewStep = $reviewSteps->get((string) $number);
            if (! $reviewStep) {
                throw ValidationException::withMessages(['review' => __('eys.competitions.invalid_review_step')]);
            }

            if ($decision['status'] === CompetitionReviewStep::STATUS_CORRECTION_REQUIRED && blank($decision['note'] ?? null)) {
                throw ValidationException::withMessages(["steps.$number.note" => __('eys.competitions.correction_note_required')]);
            }

            $reviewStep->update([
                'status' => $decision['status'],
                'note' => $decision['note'] ?? null,
                'checked_at' => $decision['status'] === CompetitionReviewStep::STATUS_PENDING ? null : now(),
                'addressed_at' => null,
                'addressed_by' => null,
            ]);
        }
    }

    /** @return array<int, CompetitionStep> */
    private function reviewableSteps(Competition $competition): array
    {
        return array_filter(
            CompetitionStepRegistry::all(),
            fn ($step, int $number) => $number <= 8 && $step->isImplemented() && $step->isApplicable($competition),
            ARRAY_FILTER_USE_BOTH,
        );
    }
}
