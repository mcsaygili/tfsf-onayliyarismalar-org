{{-- $steps: CompetitionStepRegistry::all(), $competition, $step (görüntülenen adım) --}}
<div class="ip-steps">
    @foreach ($steps as $number => $stepDef)
        @php
            $stepState = \App\Support\CompetitionWizard\CompetitionStepRegistry::stateFor($competition, $stepDef, $step);
            $isCorrectionMode = $competition->status === \App\Enums\CompetitionStatus::NeedsInfo && ($correctionSteps ?? collect())->isNotEmpty();
            $correctionStep = ($correctionSteps ?? collect())->get($number);
            $isCorrectionStep = $correctionStep !== null;
            $isCorrectionSummary = $isCorrectionMode && $number === 11;
            $isLocked = $isCorrectionMode ? ! $isCorrectionStep && ! $isCorrectionSummary : $stepState === 'locked';
            $isUnavailable = $stepState === 'not_applicable';
            $isDone = $isCorrectionMode ? $isCorrectionStep && $correctionStep?->addressed_at !== null : $stepState === 'complete';
            $isIncomplete = $isCorrectionMode ? $isCorrectionStep && ! $isDone : $stepState === 'incomplete';
            $isCurrent = $isCorrectionMode ? $number === $step : $stepState === 'current';
            $isComingSoon = ! $stepDef->isImplemented();
            $inactiveHint = data_get(
                trans('institution.competitions.steps'),
                $number.'.inactive_hint',
                trans('institution.competitions.step_states.not_applicable')
            );
            $tooltip = $isComingSoon
                ? $inactiveHint
                : ($isLocked
                ? ($isCorrectionMode ? trans('institution.competitions.correction_locked_step') : trans('institution.competitions.step_states.locked'))
                : ($isUnavailable ? $inactiveHint : null));
        @endphp
        @if ($isLocked || $isUnavailable || $isComingSoon)
            <span
                class="ip-step has-tooltip {{ $isComingSoon || $isUnavailable ? 'is-unavailable' : 'is-locked' }}"
                tabindex="0"
                aria-disabled="true"
                aria-label="{{ $stepDef->label() }} — {{ $tooltip }}"
                data-tooltip="{{ $tooltip }}"
            >
                <span class="ip-step-dot">{{ $number }}</span>
                {{ $stepDef->label() }}
            </span>
        @else
            <a
                href="{{ route('institution.competitions.step.show', [$competition, $number]) }}"
                class="ip-step {{ $isDone ? 'is-done' : '' }} {{ $isIncomplete ? 'is-incomplete' : '' }} {{ $isCurrent ? 'is-current' : '' }}"
                @if ($isCurrent) aria-current="step" @endif
            >
                <span class="ip-step-dot">{{ $number }}</span>
                {{ $stepDef->label() }}
            </a>
        @endif
    @endforeach
</div>

@if (($currentCorrection = ($correctionSteps ?? collect())->get($step)))
    <div class="ip-alert ip-alert-warning ip-correction-alert" role="alert">
        <x-institution.icon name="warning" />
        <div>
            <div class="ip-alert-title">{{ __('institution.competitions.correction_title') }}</div>
            <div class="ip-alert-text">{{ $currentCorrection->note }}</div>
            <div class="ip-correction-state">
                {{ $currentCorrection->addressed_at
                    ? __('institution.competitions.correction_addressed')
                    : __('institution.competitions.correction_pending') }}
            </div>
        </div>
    </div>
@endif
