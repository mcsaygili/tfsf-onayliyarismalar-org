{{-- $steps: CompetitionStepRegistry::all(), $competition, $step (görüntülenen adım) --}}
<div class="ip-steps">
    @foreach ($steps as $number => $stepDef)
        @php
            $stepState = \App\Support\CompetitionWizard\CompetitionStepRegistry::stateFor($competition, $stepDef, $step);
            $isLocked = $stepState === 'locked';
            $isUnavailable = $stepState === 'not_applicable';
            $isDone = $stepState === 'complete';
            $isIncomplete = $stepState === 'incomplete';
            $isCurrent = $stepState === 'current';
            $isComingSoon = ! $stepDef->isImplemented();
            $inactiveHint = data_get(
                trans('institution.competitions.steps'),
                $number.'.inactive_hint',
                trans('institution.competitions.step_states.not_applicable')
            );
            $tooltip = $isComingSoon
                ? $inactiveHint
                : ($isLocked
                ? trans('institution.competitions.step_states.locked')
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
