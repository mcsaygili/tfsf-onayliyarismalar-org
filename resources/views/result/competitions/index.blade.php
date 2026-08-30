<x-result.layout :title="__('result.all_results')">
    <div class="rr-kicker">{{ __('result.archive_kicker') }}</div>
    <h1 class="rr-title">{{ __('result.archive_title') }}</h1>
    <p class="rr-lead">{{ __('result.archive_intro') }}</p>
    <form class="rr-search" method="GET" action="{{ route('result.index') }}" role="search"><label class="sr-only" for="result-search">{{ __('result.search') }}</label><input id="result-search" class="rr-input" type="search" name="q" value="{{ $search }}" placeholder="{{ __('result.search_placeholder') }}"><button class="rr-btn">{{ __('result.search_action') }}</button></form>
    <div class="rr-grid">
        @forelse($competitions as $competition)
            <a class="rr-card" href="{{ route('result.competitions.show', $competition) }}"><span class="rr-kicker">{{ $competition->competitionType?->name ?? __('result.approved_competition') }}</span><h2>{{ $competition->name }}</h2><p>{{ $competition->institution?->name }}</p><div class="rr-meta"><span class="rr-pill">{{ trans_choice('result.category_count', $competition->categories_count, ['count' => $competition->categories_count]) }}</span><span class="rr-pill">{{ trans_choice('result.participant_count', $competition->participant_count, ['count' => $competition->participant_count]) }}</span><span class="rr-pill">{{ $competition->results_published_at->format('d.m.Y') }}</span></div></a>
        @empty
            <div class="rr-empty" style="grid-column:1/-1"><strong>{{ __('result.no_results') }}</strong><p>{{ __('result.no_results_hint') }}</p></div>
        @endforelse
    </div>
    {{ $competitions->links() }}
</x-result.layout>
