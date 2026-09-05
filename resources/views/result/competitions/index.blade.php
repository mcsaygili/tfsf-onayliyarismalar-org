<x-result.layout :title="__('result.all_results')">
    <div class="rr-kicker">{{ __('result.archive_kicker') }}</div>
    <h1 class="rr-title">{{ __('result.archive_title') }}</h1>
    <p class="rr-lead">{{ __('result.archive_intro') }}</p>
    <form class="rr-search" method="GET" action="{{ route('result.index') }}" role="search"><label class="sr-only" for="result-search">{{ __('result.search') }}</label><input id="result-search" class="rr-input" type="search" name="q" value="{{ $search }}" placeholder="{{ __('result.search_placeholder') }}"><button class="rr-btn">{{ __('result.search_action') }}</button></form>
    <div class="rr-grid">
        @forelse($publications as $publication)
            @php
                $snapshot = $publication->snapshot;
                $name = $presentation->translated(data_get($snapshot, 'competition.name', []));
                $type = $presentation->translated(data_get($snapshot, 'competition.type', []));
                $categoryCount = isset($snapshot['categories']) ? count($snapshot['categories']) : collect($snapshot['results'] ?? [])->pluck('category_id')->unique()->count();
            @endphp
            <a class="rr-card" href="{{ route('result.competitions.show', $publication->competition_id) }}"><span class="rr-kicker">{{ $type ?: __('result.approved_competition') }}</span><h2>{{ $name }}</h2><p>{{ data_get($snapshot, 'competition.institution', '') }}</p><div class="rr-meta"><span class="rr-pill">{{ trans_choice('result.category_count', $categoryCount, ['count' => $categoryCount]) }}</span>@if(isset($snapshot['participant_count']))<span class="rr-pill">{{ trans_choice('result.participant_count', $snapshot['participant_count'], ['count' => $snapshot['participant_count']]) }}</span>@endif<span class="rr-pill">{{ $publication->published_at->format('d.m.Y') }}</span></div></a>
        @empty
            <div class="rr-empty" style="grid-column:1/-1"><strong>{{ __('result.no_results') }}</strong><p>{{ __('result.no_results_hint') }}</p></div>
        @endforelse
    </div>
    {{ $publications->links() }}
</x-result.layout>
