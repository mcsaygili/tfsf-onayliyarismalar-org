<x-uye.app-layout :title="__('uye.competitions.title')">
    <header class="mp-page-heading">
        <div><h1>{{ __('uye.competitions.title') }}</h1><p>{{ __('uye.competitions.intro') }}</p></div>
        <a class="ia-btn ia-btn-secondary" href="{{ route('competitions.entries') }}">{{ __('uye.nav.entries') }}</a>
    </header>

    <div class="mp-competition-list">
        @forelse($competitions as $competition)
            <article class="mp-competition-row">
                <div class="mp-competition-date">
                    <strong>{{ $competition->application_ends_at?->format('d') ?? '—' }}</strong>
                    <span>{{ $competition->application_ends_at?->translatedFormat('M Y') ?? __('uye.competitions.date_unknown') }}</span>
                </div>
                <div class="mp-competition-copy">
                    <div class="mp-row-meta"><span>{{ $competition->institution->name }}</span><span>·</span><span>{{ trans_choice('uye.competitions.category_count', $competition->categories_count, ['count' => $competition->categories_count]) }}</span></div>
                    <h2><a href="{{ route('competitions.show', $competition) }}">{{ $competition->name }}</a></h2>
                    <p>{{ str($competition->subject)->limit(180) }}</p>
                </div>
                <div class="mp-row-action">
                    <span class="mp-state is-{{ $competition->operational_phase->value }}">{{ __('uye.competitions.phases.'.$competition->operational_phase->value) }}</span>
                    <a href="{{ route('competitions.show', $competition) }}">{{ __('uye.competitions.view') }} →</a>
                </div>
            </article>
        @empty
            <div class="mp-empty"><strong>{{ __('uye.competitions.empty_title') }}</strong><p>{{ __('uye.competitions.empty_text') }}</p></div>
        @endforelse
    </div>

    {{ $competitions->links() }}
</x-uye.app-layout>
