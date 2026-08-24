<x-institution.app-layout :title="__('institution.nav.competitions')">
    @include('institution.competitions._steps')

    <div class="ip-card" style="text-align: center; padding: 3rem 1.5rem;">
        <div class="ip-section-title">{{ $stepDef->label() }}</div>
        <div class="ip-section-hint" style="margin-bottom: 0;">{{ __('institution.competitions.coming_soon') }}</div>
    </div>

    @if ($step < \App\Support\CompetitionWizard\CompetitionStepRegistry::TOTAL_STEPS)
        <form method="POST" action="{{ route('institution.competitions.step.update', [$competition, $step]) }}" style="margin-top: 1.5rem; display: flex; gap: .75rem;">
            @csrf
            @method('PUT')
            <button type="submit" name="action" value="next" class="ia-btn">{{ __('institution.competitions.next_step') }} →</button>
        </form>
    @else
        @include('institution.competitions._submit-cta')
        @unless ($competition->canSubmit())
            <div class="ip-alert ip-alert-warning" role="status" style="margin-top: 1.5rem;">
                <x-institution.icon name="warning" />
                <div class="ip-alert-text">{{ __('institution.competitions.unimplemented_steps_block_submission') }}</div>
            </div>
        @endunless
    @endif
</x-institution.app-layout>
