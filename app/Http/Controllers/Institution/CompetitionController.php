<?php

namespace App\Http\Controllers\Institution;

use App\Enums\CompetitionStatus;
use App\Http\Controllers\Controller;
use App\Models\Competition;
use App\Models\CompetitionReviewStep;
use App\Services\CompetitionStateMachine;
use App\Support\CompetitionRegulations\CompetitionRegulationCompiler;
use App\Support\CompetitionWizard\CompetitionStepRegistry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Kurumun yarışma başvurularının listesi + yeni taslak başlatma + onaya
 * gönderme (bkz. proje planı "Kurum Paneli — Yarışma Ekleme Sihirbazı").
 * Adım adım doldurma CompetitionStepController'da.
 */
class CompetitionController extends Controller
{
    public function index(): View
    {
        $institution = Auth::guard('institution')->user()->institution;

        return view('institution.competitions.index', [
            'institution' => $institution,
            'competitions' => $institution->competitions()->with('translations')->latest()->paginate(10),
        ]);
    }

    public function store(): RedirectResponse
    {
        $staff = Auth::guard('institution')->user();

        if (! $staff->institution->hasCompleteProfile()) {
            return redirect()->route('institution.competitions.index');
        }

        $competition = $staff->institution->competitions()->create([
            'institution_staff_id' => $staff->id,
        ]);

        return redirect()->route('institution.competitions.step.show', [$competition, 1]);
    }

    public function submit(Competition $competition): RedirectResponse
    {
        Gate::forUser(Auth::guard('institution')->user())->authorize('update', $competition);

        abort_unless($competition->isEditable(), 403);
        if (! $competition->canSubmit()) {
            $blockingStep = CompetitionStepRegistry::firstBlockingStepNumber($competition) ?? $competition->current_step;

            return redirect()
                ->route('institution.competitions.step.show', [$competition, $blockingStep])
                ->with('error', __('institution.competitions.cannot_submit_incomplete'));
        }

        $fromStatus = $competition->status;

        if ($fromStatus === CompetitionStatus::NeedsInfo) {
            $unaddressedCorrection = $competition->latestReview()?->steps
                ->contains(fn (CompetitionReviewStep $step) => $step->status === CompetitionReviewStep::STATUS_CORRECTION_REQUIRED
                    && $step->addressed_at === null
                ) ?? false;

            if ($unaddressedCorrection) {
                $firstStep = $competition->latestReview()?->steps
                    ->first(fn (CompetitionReviewStep $step) => $step->status === CompetitionReviewStep::STATUS_CORRECTION_REQUIRED
                        && $step->addressed_at === null
                    )?->step_number ?? $competition->current_step;

                return redirect()
                    ->route('institution.competitions.step.show', [$competition, $firstStep])
                    ->with('error', __('institution.competitions.corrections_not_addressed'));
            }
        }

        DB::transaction(function () use ($competition, $fromStatus) {
            app(CompetitionRegulationCompiler::class)->snapshot($competition);

            app(CompetitionStateMachine::class)->transition(
                $competition,
                CompetitionStatus::Submitted,
                $fromStatus === CompetitionStatus::NeedsInfo ? 'resubmitted' : 'submitted',
                Auth::guard('institution')->user(),
                extra: ['submitted_at' => now()],
            );
        });

        return redirect()->route('institution.competitions.index')->with('status', __('institution.competitions.submitted'));
    }
}
