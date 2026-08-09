{{-- $steps: CompetitionStepRegistry::all(), $competition, $step (görüntülenen adım) --}}
<div class="ip-steps">
    @foreach ($steps as $number => $stepDef)
        @php
            $isDone = $number < $competition->current_step;
            $isCurrent = $number === $step;
            $isLocked = $number > $competition->current_step;
        @endphp
        @if ($isLocked)
            <span class="ip-step is-locked">
                <span class="ip-step-dot">{{ $number }}</span>
                {{ $stepDef->label() }}
            </span>
        @else
            <a href="{{ route('institution.competitions.step.show', [$competition, $number]) }}" class="ip-step {{ $isDone ? 'is-done' : '' }} {{ $isCurrent ? 'is-current' : '' }}">
                <span class="ip-step-dot">{{ $number }}</span>
                {{ $stepDef->label() }}
            </a>
        @endif
    @endforeach
</div>
