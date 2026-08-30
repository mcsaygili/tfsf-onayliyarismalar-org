@props(['competition'])

@php
    $audience = $competition->audience?->value ?? 'national';
    $phase = $competition->operational_phase?->value ?? 'scheduled';
    $iconKey = $competition->competitionType?->icon_key;
    $hasResults = $competition->results_published_at?->lte(now()) ?? false;
    $infrastructure = $competition->infrastructure_provider?->value ?? 'tfsf';
@endphp

<article class="pf-competition-card is-{{ $audience }}">
    <div class="pf-card-icon"><x-public.icon :name="$iconKey" /></div>
    <div class="pf-card-content">
        <div class="pf-card-topline">
            <span class="pf-audience-label is-{{ $audience }}"><x-public.icon :name="'audience-'.$audience" />{{ __('public.audience.'.$audience) }}</span>
            <span class="pf-phase is-{{ $phase }}">{{ __('public.phases.'.$phase) }}</span>
        </div>
        <p class="pf-card-institution">{{ $competition->institution?->name }}</p>
        <h3><a href="{{ route('public.competitions.show', $competition->public_slug) }}">{{ $competition->name }}</a></h3>
        <p class="pf-card-subject">{{ str($competition->subject)->limit(150) }}</p>
        <div class="pf-card-meta">
            <span><x-public.icon name="calendar" />{{ $competition->application_ends_at?->translatedFormat('d M Y') ?? __('public.date_unknown') }}</span>
            <span><x-public.icon name="categories" />{{ trans_choice('public.category_count', $competition->categories_count, ['count' => $competition->categories_count]) }}</span>
            <span><x-public.icon :name="'infrastructure-'.$infrastructure" />{{ __('public.infrastructure.'.$infrastructure) }}</span>
        </div>
        <div class="pf-card-actions">
            <a class="pf-text-link" href="{{ route('public.competitions.show', $competition->public_slug) }}">{{ __('public.view_competition') }}<x-public.icon name="arrow-right" /></a>
            @if($hasResults)
                <a class="pf-text-link is-secondary" href="{{ route('result.competitions.show', $competition) }}">{{ __('public.view_results') }}</a>
            @endif
        </div>
    </div>
</article>
