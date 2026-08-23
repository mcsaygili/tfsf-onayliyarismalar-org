{{-- $steps: CompetitionStepRegistry::all(), $competition, $step (görüntülenen adım) --}}
<div class="ip-steps">
    @foreach ($steps as $number => $stepDef)
        @php
            $isApplicable = $stepDef->isApplicable($competition);
            $isLocked = $number > $competition->current_step;
            $isUnavailable = ! $isLocked && ! $isApplicable;
            $isDone = $isApplicable && $number < $competition->current_step;
            $isCurrent = $isApplicable && $number === $step;
            $inactiveHint = data_get(
                trans('institution.competitions.steps'),
                $number.'.inactive_hint',
                trans('institution.competitions.step_states.not_applicable')
            );
            $tooltip = $isLocked
                ? trans('institution.competitions.step_states.locked')
                : ($isUnavailable ? $inactiveHint : null);
        @endphp
        @if ($isLocked || $isUnavailable)
            <span
                class="ip-step has-tooltip {{ $isLocked ? 'is-locked' : 'is-unavailable' }}"
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
                class="ip-step {{ $isDone ? 'is-done' : '' }} {{ $isCurrent ? 'is-current' : '' }}"
                @if ($isCurrent) aria-current="step" @endif
            >
                <span class="ip-step-dot">{{ $number }}</span>
                {{ $stepDef->label() }}
            </a>
        @endif
    @endforeach
</div>
