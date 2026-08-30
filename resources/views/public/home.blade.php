<x-public.layout>
    <section class="pf-hero">
        <div class="pf-shell pf-hero-grid">
            <div class="pf-hero-copy">
                <h1>{{ __('public.home.title') }}</h1>
                <p>{{ __('public.home.intro') }}</p>
                <div class="pf-hero-actions">
                    <a class="pf-button" href="{{ route('public.competitions.index') }}">{{ __('public.home.explore') }}<x-public.icon name="arrow-right" /></a>
                    <a class="pf-button is-quiet" href="{{ route('result.index') }}">{{ __('public.home.results') }}</a>
                </div>
            </div>
            <dl class="pf-public-stats" aria-label="{{ __('public.home.stats_label') }}">
                <div><dt>{{ __('public.home.all_competitions') }}</dt><dd>{{ $counts['all'] }}</dd></div>
                <div><dt>{{ __('public.home.open_competitions') }}</dt><dd>{{ $counts['open'] }}</dd></div>
                <div class="is-national"><dt>{{ __('public.audience.national') }}</dt><dd>{{ $counts['national'] }}</dd></div>
                <div class="is-international"><dt>{{ __('public.audience.international') }}</dt><dd>{{ $counts['international'] }}</dd></div>
            </dl>
        </div>
    </section>

    <section class="pf-audience-choice" aria-labelledby="audience-choice-title">
        <div class="pf-shell">
            <div class="pf-section-heading">
                <div><h2 id="audience-choice-title">{{ __('public.home.audience_title') }}</h2><p>{{ __('public.home.audience_intro') }}</p></div>
            </div>
            <div class="pf-audience-grid">
                <a class="pf-audience-panel is-national" href="{{ route('public.competitions.index', ['audience' => 'national']) }}">
                    <span class="pf-audience-icon"><x-public.icon name="audience-national" /></span>
                    <span><strong>{{ __('public.audience.national') }}</strong><small>{{ __('public.home.national_description') }}</small></span>
                    <x-public.icon name="arrow-right" />
                </a>
                <a class="pf-audience-panel is-international" href="{{ route('public.competitions.index', ['audience' => 'international']) }}">
                    <span class="pf-audience-icon"><x-public.icon name="audience-international" /></span>
                    <span><strong>{{ __('public.audience.international') }}</strong><small>{{ __('public.home.international_description') }}</small></span>
                    <x-public.icon name="arrow-right" />
                </a>
            </div>
        </div>
    </section>

    @if($openCompetitions->isNotEmpty())
        <section class="pf-section">
            <div class="pf-shell">
                <div class="pf-section-heading"><div><h2>{{ __('public.home.open_title') }}</h2><p>{{ __('public.home.open_intro') }}</p></div><a href="{{ route('public.competitions.index', ['phase' => 'open']) }}">{{ __('public.view_all') }}<x-public.icon name="arrow-right" /></a></div>
                <div class="pf-card-grid">@foreach($openCompetitions as $competition)<x-public.competition-card :competition="$competition" />@endforeach</div>
            </div>
        </section>
    @endif

    <section class="pf-section pf-section-divided">
        <div class="pf-shell">
            <div class="pf-section-heading is-national"><div><h2>{{ __('public.home.national_title') }}</h2><p>{{ __('public.home.national_intro') }}</p></div><a href="{{ route('public.competitions.index', ['audience' => 'national']) }}">{{ __('public.view_all') }}<x-public.icon name="arrow-right" /></a></div>
            @if($nationalCompetitions->isNotEmpty())<div class="pf-card-grid">@foreach($nationalCompetitions as $competition)<x-public.competition-card :competition="$competition" />@endforeach</div>@else<x-public.empty-state />@endif
        </div>
    </section>

    <section class="pf-section pf-section-international">
        <div class="pf-shell">
            <div class="pf-section-heading is-international"><div><h2>{{ __('public.home.international_title') }}</h2><p>{{ __('public.home.international_intro') }}</p></div><a href="{{ route('public.competitions.index', ['audience' => 'international']) }}">{{ __('public.view_all') }}<x-public.icon name="arrow-right" /></a></div>
            @if($internationalCompetitions->isNotEmpty())<div class="pf-card-grid">@foreach($internationalCompetitions as $competition)<x-public.competition-card :competition="$competition" />@endforeach</div>@else<x-public.empty-state />@endif
        </div>
    </section>
</x-public.layout>
