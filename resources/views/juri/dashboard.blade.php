<x-juri.app-layout :title="__('juri.nav.dashboard')">
    @if (blank($juri->first_name) || blank($juri->last_name))
        <div class="ip-alert ip-alert-warning">
            <x-juri.icon name="warning" />
            <div>
                <div class="ip-alert-title">{{ __('juri.dashboard.incomplete_title') }}</div>
                <div class="ip-alert-text">
                    {{ __('juri.dashboard.incomplete_text') }}
                    <a href="{{ route('juri.profile.edit') }}">{{ __('juri.dashboard.incomplete_link') }}</a>
                </div>
            </div>
        </div>
    @endif

    <header class="jp-page-heading">
        <div>
            <h1>{{ __('juri.dashboard.welcome', ['name' => $juri->first_name ?? $juri->email]) }}</h1>
            <p>{{ __('juri.dashboard.hint') }}</p>
        </div>
        @if ($competitionCount > 0)
            <a class="ia-btn ia-btn-secondary jp-heading-action" href="{{ route('juri.assignments.index') }}">{{ __('juri.dashboard.all_assignments') }} →</a>
        @endif
    </header>

    <div class="jp-task-summary" aria-label="{{ __('juri.assignments.summary') }}">
        <span><strong>{{ $competitionCount }}</strong> {{ __('juri.assignments.competition_count') }}</span>
        <span aria-hidden="true">·</span>
        <span><strong>{{ $assignmentCount }}</strong> {{ __('juri.assignments.category_count') }}</span>
        @if ($nextMilestone)
            <span aria-hidden="true">·</span>
            <span>{{ __('juri.assignments.next_milestone') }}: <strong>{{ $nextMilestone['at']->format('d.m.Y H:i') }}</strong></span>
        @endif
    </div>

    <section aria-labelledby="dashboard-assignments-title">
        <div class="jp-section-heading">
            <div>
                <h2 id="dashboard-assignments-title">{{ __('juri.dashboard.recent_assignments') }}</h2>
                <p>{{ __('juri.dashboard.recent_assignments_hint') }}</p>
            </div>
        </div>
        @include('juri.assignments._list', ['competitions' => $competitions, 'compact' => true])
    </section>
</x-juri.app-layout>
