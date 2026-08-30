<x-juri.app-layout :title="__('juri.nav.assignments')">
    <header class="jp-page-heading">
        <div>
            <h1>{{ __('juri.assignments.title') }}</h1>
            <p>{{ __('juri.assignments.hint') }}</p>
        </div>
    </header>

    <div class="jp-task-summary" aria-label="{{ __('juri.assignments.summary') }}">
        <span><strong>{{ $competitions->total() }}</strong> {{ __('juri.assignments.competition_count') }}</span>
        <span aria-hidden="true">·</span>
        <span><strong>{{ $assignmentCount }}</strong> {{ __('juri.assignments.category_count') }}</span>
        @if ($nextMilestone)
            <span aria-hidden="true">·</span>
            <span>{{ __('juri.assignments.next_milestone') }}: <strong>{{ __('juri.assignments.dates.'.$nextMilestone['label']) }} · {{ $nextMilestone['at']->format('d.m.Y H:i') }}</strong></span>
        @endif
    </div>

    @include('juri.assignments._list', ['competitions' => $competitions])

    @if ($competitions->hasPages())
        <nav class="jp-pagination" aria-label="{{ __('juri.assignments.pagination') }}">
            @if ($competitions->onFirstPage())
                <span class="is-disabled">← {{ __('juri.assignments.previous') }}</span>
            @else
                <a href="{{ $competitions->previousPageUrl() }}">← {{ __('juri.assignments.previous') }}</a>
            @endif
            <span>{{ __('juri.assignments.page', ['current' => $competitions->currentPage(), 'last' => $competitions->lastPage()]) }}</span>
            @if ($competitions->hasMorePages())
                <a href="{{ $competitions->nextPageUrl() }}">{{ __('juri.assignments.next') }} →</a>
            @else
                <span class="is-disabled">{{ __('juri.assignments.next') }} →</span>
            @endif
        </nav>
    @endif
</x-juri.app-layout>
