<?php

namespace App\Http\Controllers\Eys;

use App\Enums\CompetitionStatus;
use App\Http\Controllers\Controller;
use App\Models\Competition;
use App\Models\CompetitionReview;
use App\Models\CompetitionReviewStep;
use App\Services\CompetitionReadinessService;
use App\Services\CompetitionWorkflowService;
use App\Support\CompetitionRegulations\CompetitionRegulationCompiler;
use App\Support\CompetitionWizard\CompetitionStep;
use App\Support\CompetitionWizard\CompetitionStepRegistry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
        ]);

        $latestSnapshot = $competition->regulationSnapshots->sortByDesc('version')->first();

        return view('eys.competitions.show', [
            'competition' => $competition,
            'steps' => CompetitionStepRegistry::all(),
            'reviewableSteps' => $this->reviewableSteps($competition),
            'latestReview' => $competition->reviews->sortByDesc('round')->first(),
            'pendingJuryAssignments' => app(CompetitionReadinessService::class)->pendingJuryAssignments($competition),
            'compiledRegulation' => $latestSnapshot?->content
                ?? app(CompetitionRegulationCompiler::class)->preview($competition)['content'],
            'regulationSnapshot' => $latestSnapshot,
        ]);
    }

    public function start(Competition $competition, CompetitionWorkflowService $workflow): RedirectResponse
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

    public function approve(Competition $competition, CompetitionReadinessService $readiness, CompetitionWorkflowService $workflow): RedirectResponse
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

            return back()->withErrors(['approval' => __('eys.competitions.jury_approval_blocked')]);
        }

        DB::transaction(function () use ($competition, $workflow, $review): void {
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
                ],
            );
        });

        return redirect()->route('eys.competitions.index')->with('status', __('eys.competitions.approved'));
    }

    public function reject(Request $request, Competition $competition, CompetitionWorkflowService $workflow): RedirectResponse
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

    public function requestInfo(Request $request, Competition $competition, CompetitionWorkflowService $workflow): RedirectResponse
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
