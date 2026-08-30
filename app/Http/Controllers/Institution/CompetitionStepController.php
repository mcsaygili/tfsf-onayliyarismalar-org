<?php

namespace App\Http\Controllers\Institution;

use App\Enums\CompetitionStatus;
use App\Http\Controllers\Controller;
use App\Models\Competition;
use App\Models\CompetitionStatusLog;
use App\Models\Country;
use App\Models\Juri;
use App\Models\JuryInvitation;
use App\Services\CompetitionReadinessService;
use App\Services\JuryInvitationService;
use App\Support\CompetitionWizard\CompetitionStepRegistry;
use App\Support\CompetitionWizard\Step10;
use App\Support\CompetitionWizard\Step4;
use App\Support\CompetitionWizard\Step5;
use App\Support\CompetitionWizard\Step6;
use App\Support\CompetitionWizard\Step7;
use App\Support\CompetitionWizard\Step8;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

/**
 * Sihirbazın tek bir adımının gösterimi + kaydı — "taslak kaydet" (kısmi
 * veri, doğrulama yok) ile "ileri" (o adımın tüm kuralları geçmeli, adımı
 * ilerletir) aynı formda iki ayrı submit butonu (name="action") olarak
 * ayrılıyor (bkz. proje planı).
 */
class CompetitionStepController extends Controller
{
    public function show(Competition $competition, int $step): View|RedirectResponse
    {
        $this->authorizeSameInstitution($competition);

        if ($step > $competition->current_step) {
            return redirect()->route('institution.competitions.step.show', [$competition, $competition->current_step]);
        }

        $competition->loadMissing('translations');

        $stepDef = CompetitionStepRegistry::get($step);

        if (! $stepDef->isApplicable($competition)) {
            $nextStep = CompetitionStepRegistry::nextApplicableStepNumber($step, $competition);

            if ($competition->current_step === $step && $nextStep > $step) {
                $competition->forceFill(['current_step' => $nextStep])->save();
            }

            return redirect()->route('institution.competitions.step.show', [$competition, $nextStep]);
        }

        $view = $stepDef->isImplemented()
            ? 'institution.competitions.step-'.$step
            : 'institution.competitions.step-placeholder';

        $viewData = [
            'competition' => $competition,
            'step' => $step,
            'stepDef' => $stepDef,
            'steps' => CompetitionStepRegistry::all(),
        ];

        if ($stepDef instanceof Step4) {
            $viewData['competitionTypes'] = $stepDef->options();
        }

        if ($stepDef instanceof Step5) {
            $viewData['countries'] = $stepDef->countries();
            $viewData['regionFormData'] = $stepDef->formRegions($competition);
            $viewData['regionCities'] = collect($viewData['regionFormData'])
                ->pluck('country')
                ->filter()
                ->unique()
                ->mapWithKeys(fn (string $countryId) => [
                    $countryId => $stepDef->cities($countryId)
                        ->map(fn ($city) => ['id' => $city->id, 'name' => $city->official_name])
                        ->values(),
                ]);
            $viewData['approvalProcesses'] = $stepDef->approvalProcesses();
        }

        if ($stepDef instanceof Step6) {
            $viewData['categoryFormData'] = $stepDef->formData($competition);
            $viewData = array_merge($viewData, $stepDef->options());
        }

        if ($stepDef instanceof Step7) {
            $viewData['categoryAwardFormData'] = $stepDef->formData($competition);
            $viewData['awardReferences'] = $stepDef->options($competition);
            $viewData['categories'] = $competition->categories()->with('translations')->orderBy('sort_order')->get();
        }

        if ($stepDef instanceof Step8) {
            $viewData['categoryJurorFormData'] = $stepDef->formData($competition);
            $viewData['categories'] = $competition->categories()->with('translations')->orderBy('sort_order')->get();
        }

        if ($stepDef instanceof Step10) {
            $competition->loadMissing([
                'institution',
                'institutionStaff',
                'competitionType.translations',
                'captureRegions.country.translations',
                'captureRegions.city.translations',
                'participantApprovalProcess.translations',
                'categories.translations',
                'categories.ageEligibilityRule.translations',
                'categories.genders.translations',
                'categories.memberGroups.translations',
                'categories.captureDevices.translations',
                'categories.processingMethods.translations',
                'categories.awards.translations',
                'categories.awards.awardReference.translations',
                'categories.jurorAssignments.juror',
                'categories.jurorAssignments.invitation',
            ]);

            $readiness = app(CompetitionReadinessService::class);
            $viewData['submissionChecks'] = $readiness->submissionChecks($competition);
            $viewData['submissionBlockers'] = array_values(array_filter(
                $viewData['submissionChecks'],
                fn (array $check): bool => $check['blocking'],
            ));
            $viewData['pendingJuryAssignments'] = $readiness->pendingJuryAssignments($competition);
            $viewData['submissionReady'] = $viewData['submissionBlockers'] === [];
        }

        return view($view, $viewData);
    }

    public function cities(Country $country): JsonResponse
    {
        abort_unless($country->status, 404);

        $cities = (new Step5)->cities($country->id)
            ->map(fn ($city) => [
                'id' => $city->id,
                'name' => $city->official_name,
            ])
            ->values();

        return response()->json($cities);
    }

    public function update(Request $request, Competition $competition, int $step): RedirectResponse
    {
        $this->authorizeSameInstitution($competition);

        abort_unless($competition->isEditable(), 403);

        $stepDef = CompetitionStepRegistry::get($step);
        abort_unless($stepDef->isApplicable($competition), 404);
        $isDraftSave = $request->input('action') === 'draft';

        $validated = Validator::make(
            $request->all(),
            $stepDef->rules($isDraftSave, $competition)
        )->validate();

        $wasNeedsInfo = $competition->status === CompetitionStatus::NeedsInfo;
        $before = $wasNeedsInfo ? $stepDef->data($competition) : null;

        DB::transaction(function () use ($competition, $validated, $wasNeedsInfo, $before, $stepDef) {
            $stepDef->persist($competition, $validated);

            if ($wasNeedsInfo && $before !== null) {
                $this->logFieldChanges($competition, $before, $stepDef->data($competition));
            }
        });

        if (! $isDraftSave && $stepDef instanceof Step8) {
            $stepDef->sendPendingInvitations($competition, app(JuryInvitationService::class));
        }

        if ($isDraftSave) {
            return redirect()
                ->route('institution.competitions.step.show', [$competition, $step])
                ->with('status', __('institution.competitions.draft_saved'));
        }

        $competition->refresh();
        $nextStep = CompetitionStepRegistry::nextApplicableStepNumber($step, $competition);
        $furthestStep = max($competition->current_step, $nextStep);
        $firstIncompleteStep = CompetitionStepRegistry::firstIncompleteStepNumber($competition);

        $competition->forceFill([
            'current_step' => $firstIncompleteStep !== null && $firstIncompleteStep < $furthestStep
                ? $firstIncompleteStep
                : $furthestStep,
        ])->save();

        return redirect()->route('institution.competitions.step.show', [$competition, $nextStep]);
    }

    public function jurors(Request $request, Competition $competition): JsonResponse
    {
        $this->authorizeSameInstitution($competition);
        abort_unless($competition->isEditable(), 403);

        $search = trim((string) $request->input('q'));
        if (mb_strlen($search) < 2) {
            return response()->json([]);
        }

        return response()->json(Juri::query()
            ->where('status', true)
            ->whereNotNull('email_verified_at')
            ->whereNotNull('first_name')
            ->whereNotNull('last_name')
            ->where(function ($query) use ($search) {
                $query->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            })
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->limit(10)
            ->get()
            ->map(fn (Juri $juror) => [
                'id' => $juror->id,
                'name' => trim($juror->first_name.' '.$juror->last_name),
                'email' => $juror->email,
            ]));
    }

    public function resendJuryInvitation(Competition $competition, JuryInvitation $invitation, JuryInvitationService $service): RedirectResponse
    {
        $this->authorizeSameInstitution($competition);
        abort_unless($competition->isEditable(), 403);
        abort_unless($invitation->competition_id === $competition->id && $invitation->isPending(), 404);
        abort_unless($invitation->assignments()->exists(), 422);

        $service->send($invitation);

        return back()->with('status', __('institution.competitions.jury_invitation_resent'));
    }

    /**
     * @param  array<string, mixed>  $before
     * @param  array<string, mixed>  $after
     */
    private function logFieldChanges(Competition $competition, array $before, array $after): void
    {
        $changes = [];
        $before = Arr::dot($before);
        $after = Arr::dot($after);

        foreach (array_unique([...array_keys($before), ...array_keys($after)]) as $field) {
            if (($before[$field] ?? null) !== ($after[$field] ?? null)) {
                $changes[$field] = [$before[$field] ?? null, $after[$field] ?? null];
            }
        }

        if ($changes === []) {
            return;
        }

        CompetitionStatusLog::create([
            'competition_id' => $competition->id,
            'action' => 'field_updated',
            'from_status' => CompetitionStatus::NeedsInfo->value,
            'to_status' => CompetitionStatus::NeedsInfo->value,
            'changes' => $changes,
            'actor_id' => Auth::guard('institution')->id(),
            'actor_type' => Auth::guard('institution')->user()::class,
        ]);
    }

    private function authorizeSameInstitution(Competition $competition): void
    {
        if ($competition->institution_id !== Auth::guard('institution')->user()->institution_id) {
            throw new AuthorizationException;
        }
    }
}
