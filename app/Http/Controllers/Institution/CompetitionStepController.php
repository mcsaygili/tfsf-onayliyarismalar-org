<?php

namespace App\Http\Controllers\Institution;

use App\Enums\CompetitionStatus;
use App\Http\Controllers\Controller;
use App\Models\Competition;
use App\Models\CompetitionStatusLog;
use App\Support\CompetitionWizard\CompetitionStepRegistry;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

        $stepDef = CompetitionStepRegistry::get($step);

        $view = $stepDef->isImplemented()
            ? 'institution.competitions.step-'.$step
            : 'institution.competitions.step-placeholder';

        return view($view, [
            'competition' => $competition,
            'step' => $step,
            'stepDef' => $stepDef,
            'steps' => CompetitionStepRegistry::all(),
        ]);
    }

    public function update(Request $request, Competition $competition, int $step): RedirectResponse
    {
        $this->authorizeSameInstitution($competition);

        abort_unless($competition->isEditable(), 403);

        $stepDef = CompetitionStepRegistry::get($step);
        $isDraftSave = $request->input('action') === 'draft';

        $validated = Validator::make(
            $request->all(),
            $stepDef->rules($isDraftSave)
        )->validate();

        $wasNeedsInfo = $competition->status === CompetitionStatus::NeedsInfo;
        $before = $wasNeedsInfo ? $competition->only($stepDef->fillable()) : null;

        DB::transaction(function () use ($competition, $validated, $wasNeedsInfo, $before, $stepDef) {
            $competition->update($validated);

            if ($wasNeedsInfo && $before !== null) {
                $this->logFieldChanges($competition, $stepDef->fillable(), $before);
            }
        });

        if ($isDraftSave) {
            return redirect()
                ->route('institution.competitions.step.show', [$competition, $step])
                ->with('status', __('institution.competitions.draft_saved'));
        }

        $competition->forceFill([
            'current_step' => max($competition->current_step, $step + 1),
        ])->save();

        $nextStep = min($step + 1, CompetitionStepRegistry::TOTAL_STEPS);

        return redirect()->route('institution.competitions.step.show', [$competition, $nextStep]);
    }

    /**
     * @param  array<int, string>  $fields
     * @param  array<string, mixed>  $before
     */
    private function logFieldChanges(Competition $competition, array $fields, array $before): void
    {
        $changes = [];

        foreach ($fields as $field) {
            if ($before[$field] !== $competition->{$field}) {
                $changes[$field] = [$before[$field], $competition->{$field}];
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
