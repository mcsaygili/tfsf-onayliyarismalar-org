<?php

namespace App\Http\Controllers\Institution;

use App\Enums\CompetitionStatus;
use App\Http\Controllers\Controller;
use App\Models\Competition;
use App\Models\CompetitionStatusLog;
use App\Models\Country;
use App\Support\CompetitionWizard\CompetitionStepRegistry;
use App\Support\CompetitionWizard\Step4;
use App\Support\CompetitionWizard\Step5;
use App\Support\CompetitionWizard\Step6;
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
            $viewData['cities'] = $stepDef->cities(old('country', $competition->country_id));
            $viewData['approvalProcesses'] = $stepDef->approvalProcesses();
        }

        if ($stepDef instanceof Step6) {
            $viewData['categoryFormData'] = $stepDef->formData($competition);
            $viewData = array_merge($viewData, $stepDef->options());
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
