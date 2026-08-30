<x-public.layout :title="__('public.catalogue.title')" :description="__('public.catalogue.intro')">
    <section class="pf-page-hero">
        <div class="pf-shell">
            <h1>{{ __('public.catalogue.title') }}</h1>
            <p>{{ __('public.catalogue.intro') }}</p>
        </div>
    </section>

    <section class="pf-catalogue">
        <div class="pf-shell">
            <nav class="pf-audience-tabs" aria-label="{{ __('public.catalogue.audience_filter') }}">
                @foreach(['all', 'national', 'international'] as $audience)
                    @php $url = $audience === 'all' ? route('public.competitions.index', request()->except(['audience', 'page'])) : route('public.competitions.index', array_merge(request()->except('page'), ['audience' => $audience])); @endphp
                    <a href="{{ $url }}" @class(['is-active' => ($filters['audience'] ?? null) === ($audience === 'all' ? null : $audience), 'is-national' => $audience === 'national', 'is-international' => $audience === 'international'])>
                        @if($audience !== 'all')<x-public.icon :name="'audience-'.$audience" />@endif
                        {{ __('public.audience.'.$audience) }}<span>{{ $audienceCounts[$audience] }}</span>
                    </a>
                @endforeach
            </nav>

            <form class="pf-filter-panel" method="GET" action="{{ route('public.competitions.index') }}">
                @if($filters['audience'])<input type="hidden" name="audience" value="{{ $filters['audience'] }}">@endif
                <label class="pf-search-field"><span>{{ __('public.catalogue.search_label') }}</span><span class="pf-input-wrap"><x-public.icon name="search" /><input type="search" name="q" value="{{ $filters['q'] }}" placeholder="{{ __('public.catalogue.search_placeholder') }}"></span></label>
                <label><span>{{ __('public.catalogue.type') }}</span><select name="type"><option value="">{{ __('public.catalogue.all_types') }}</option>@foreach($competitionTypes as $type)<option value="{{ $type->code }}" @selected($filters['type'] === $type->code)>{{ $type->name }}</option>@endforeach</select></label>
                <label><span>{{ __('public.catalogue.phase') }}</span><select name="phase"><option value="">{{ __('public.catalogue.all_phases') }}</option>@foreach(['open', 'upcoming', 'evaluation', 'completed'] as $phase)<option value="{{ $phase }}" @selected($filters['phase'] === $phase)>{{ __('public.catalogue.phases.'.$phase) }}</option>@endforeach</select></label>
                <label><span>{{ __('public.catalogue.year') }}</span><select name="year"><option value="">{{ __('public.catalogue.all_years') }}</option>@foreach($years as $year)<option value="{{ $year }}" @selected((string)$filters['year'] === (string)$year)>{{ $year }}</option>@endforeach</select></label>
                <div class="pf-filter-actions"><button class="pf-button" type="submit"><x-public.icon name="filter" />{{ __('public.catalogue.apply') }}</button><a href="{{ route('public.competitions.index', $filters['audience'] ? ['audience' => $filters['audience']] : []) }}">{{ __('public.catalogue.clear') }}</a></div>
            </form>

            <div class="pf-results-heading"><h2>{{ trans_choice('public.catalogue.result_count', $competitions->total(), ['count' => $competitions->total()]) }}</h2><p>{{ __('public.catalogue.order_hint') }}</p></div>

            @if($competitions->isNotEmpty())
                <div class="pf-card-grid">@foreach($competitions as $competition)<x-public.competition-card :competition="$competition" />@endforeach</div>
                {{ $competitions->onEachSide(1)->links('vendor.pagination.public') }}
            @else
                <x-public.empty-state />
            @endif
        </div>
    </section>
</x-public.layout>
