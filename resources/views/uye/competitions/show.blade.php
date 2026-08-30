<x-uye.app-layout :title="$competition->name">
    <a class="mp-back" href="{{ route('competitions.index') }}">← {{ __('uye.competitions.back') }}</a>
    <header class="mp-detail-heading">
        <div>
            <p>{{ $competition->institution->name }}</p>
            <h1>{{ $competition->name }}</h1>
            <span class="mp-state is-{{ $phase->value }}">{{ __('uye.competitions.phases.'.$phase->value) }}</span>
        </div>
        <dl class="mp-timeline">
            <div><dt>{{ __('uye.competitions.application_start') }}</dt><dd>{{ $competition->application_starts_at?->format('d.m.Y H:i') ?? '—' }}</dd></div>
            <div><dt>{{ __('uye.competitions.application_end') }}</dt><dd>{{ $competition->application_ends_at?->format('d.m.Y H:i') ?? '—' }}</dd></div>
            <div><dt>{{ __('uye.competitions.competition_end') }}</dt><dd>{{ $competition->competition_ends_at?->format('d.m.Y H:i') ?? '—' }}</dd></div>
        </dl>
    </header>

    <section class="mp-prose"><h2>{{ __('uye.competitions.subject') }}</h2><p>{{ $competition->subject }}</p><h2>{{ __('uye.competitions.purpose') }}</h2><p>{{ $competition->purpose }}</p></section>

    @if(!$competitionCheck['eligible'])
        <div class="mp-callout is-warning">
            <strong>{{ __('uye.competitions.eligibility_action') }}</strong>
            <ul>@foreach($competitionCheck['violations'] as $violation)<li>{{ __('uye.competitions.violations.'.$violation) }}</li>@endforeach</ul>
            @if($competitionCheck['state'] === 'action_required')<a href="{{ route('profile.edit') }}">{{ __('uye.competitions.complete_profile') }} →</a>@endif
        </div>
    @endif

    <section class="mp-category-section">
        <header><h2>{{ __('uye.competitions.categories') }}</h2><p>{{ __('uye.competitions.categories_hint') }}</p></header>
        <div class="mp-category-list">
            @foreach($competition->categories as $category)
                @php
                    $check = $categoryChecks[$category->id];
                @endphp
                <article>
                    <div><h3>{{ $category->name }}</h3><p>{{ trans_choice('uye.competitions.photo_limit_label', $category->max_photos_per_participant, ['count' => $category->max_photos_per_participant]) }}</p></div>
                    <span class="mp-eligibility is-{{ $check['state'] }}">{{ __('uye.competitions.eligibility.'.$check['state']) }}</span>
                    @if(!$check['eligible'])<ul>@foreach($check['violations'] as $violation)<li>{{ __('uye.competitions.violations.'.$violation) }}</li>@endforeach</ul>@endif
                </article>
            @endforeach
        </div>
    </section>

    @if($phase->value === 'results_published' && $competition->evaluationRounds->first()?->results->isNotEmpty())
        <section class="mp-category-section"><header><h2>{{ __('uye.competitions.results') }}</h2><p>{{ __('uye.competitions.results_hint') }}</p></header><div class="mp-selected-photos">
            @foreach($competition->evaluationRounds->first()->results->sortBy(fn ($result) => sprintf('%s-%05d', $result->photo->submission->competition_category_id, $result->rank)) as $result)<article><img src="{{ route('competitions.photos.show', $result->photo) }}" alt=""><div><strong>#{{ $result->rank }} · {{ $result->photo->submission->category->name }}</strong><span>{{ __('uye.competitions.result_score', ['score' => $result->average_score]) }}</span>@if($result->awards->isNotEmpty())<span style="color:var(--ia-copper-bright);font-weight:700;">{{ $result->awards->map(fn ($assignment) => $assignment->categoryAward->awardReference?->name ?: $assignment->categoryAward->special_award_text)->filter()->join(' · ') }}</span>@endif</div></article>@endforeach
        </div></section>
    @endif

    <footer class="mp-action-bar">
        <div><strong>{{ __('uye.competitions.entry_title') }}</strong><span>{{ __('uye.competitions.entry_hint') }}</span></div>
        @if($entry)
            <a class="ia-btn" href="{{ route('competitions.entry.show', $entry) }}">{{ __('uye.competitions.continue_entry') }}</a>
        @else
            <form method="POST" action="{{ route('competitions.start', $competition) }}">@csrf<button class="ia-btn" @disabled(!$competitionCheck['eligible'])>{{ __('uye.competitions.start_entry') }}</button></form>
        @endif
    </footer>
</x-uye.app-layout>
