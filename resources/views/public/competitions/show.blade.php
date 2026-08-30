<x-public.layout :title="$competition->name" :description="str($competition->subject)->limit(155)">
    @php
        $audience = $competition->audience?->value ?? 'national';
        $phaseKey = $phase->value;
        $hasResults = $competition->results_published_at?->lte(now()) ?? false;
        $infrastructure = $competition->infrastructure_provider?->value ?? 'tfsf';
        $isExternal = $infrastructure === 'external';
    @endphp

    <section class="pf-detail-hero is-{{ $audience }}">
        <div class="pf-shell">
            <a class="pf-back-link" href="{{ route('public.competitions.index') }}"><x-public.icon name="back" />{{ __('public.detail.back') }}</a>
            <div class="pf-detail-hero-grid">
                <div class="pf-detail-icon"><x-public.icon :name="$competition->competitionType?->icon_key" /></div>
                <div class="pf-detail-title">
                    <div class="pf-detail-labels">
                        <span class="pf-audience-label is-{{ $audience }}"><x-public.icon :name="'audience-'.$audience" />{{ __('public.audience.'.$audience) }}</span>
                        <span class="pf-phase is-{{ $phaseKey }}">{{ __('public.phases.'.$phaseKey) }}</span>
                    </div>
                    <p>{{ $competition->institution?->name }}</p>
                    <h1>{{ $competition->name }}</h1>
                    <div class="pf-type-line"><strong>{{ $competition->competitionType?->name }}</strong><span>{{ __('public.infrastructure.'.$infrastructure) }}</span></div>
                </div>
            </div>
        </div>
    </section>

    <div class="pf-shell pf-detail-layout">
        <div class="pf-detail-content">
            <section class="pf-detail-section">
                <h2>{{ __('public.detail.schedule') }}</h2>
                <dl class="pf-schedule">
                    @foreach([
                        ['label' => __('public.detail.application_start'), 'date' => $competition->application_starts_at],
                        ['label' => __('public.detail.application_end'), 'date' => $competition->application_ends_at],
                        ['label' => __('public.detail.competition_end'), 'date' => $competition->competition_ends_at],
                        ['label' => __('public.detail.evaluation_start'), 'date' => $competition->evaluation_starts_at],
                        ['label' => __('public.detail.evaluation_end'), 'date' => $competition->evaluation_ends_at],
                    ] as $event)
                        <div><span><x-public.icon name="calendar" /></span><dt>{{ $event['label'] }}</dt><dd>@if($event['date'])<time datetime="{{ $event['date']->toIso8601String() }}">{{ $event['date']->translatedFormat('d F Y · H:i') }}</time>@else—@endif</dd></div>
                    @endforeach
                </dl>
            </section>

            <section class="pf-detail-section pf-reading">
                <h2>{{ __('public.detail.about') }}</h2>
                <h3>{{ __('public.detail.subject') }}</h3><p>{{ $competition->subject }}</p>
                <h3>{{ __('public.detail.purpose') }}</h3><p>{{ $competition->purpose }}</p>
                @if($competition->partners)<h3>{{ __('public.detail.partners') }}</h3><p>{{ $competition->partners }}</p>@endif
            </section>

            @if($competition->captureRegions->isNotEmpty() || $competition->participantApprovalProcess)
                <section class="pf-detail-section">
                    <h2>{{ __('public.detail.configuration') }}</h2>
                    <div class="pf-configuration-list">
                        @if($competition->captureRegions->isNotEmpty())
                            <article><span><x-public.icon name="location" /></span><div><h3>{{ __('public.detail.capture_regions') }}</h3><p>{{ $competition->captureRegions->map(fn($region) => collect([$region->city?->official_name, $region->country?->short_name ?: $region->country?->official_name])->filter()->join(', '))->join(' · ') }}</p><small>{{ __('public.detail.capture_regions_hint') }}</small></div></article>
                        @endif
                        @if($competition->participantApprovalProcess)
                            <article><span><x-public.icon name="approval" /></span><div><h3>{{ __('public.detail.approval_process') }}</h3><p>{{ $competition->participantApprovalProcess->name }}</p><small>{{ $competition->participantApprovalProcess->description }}</small></div></article>
                        @endif
                    </div>
                </section>
            @endif

            <section class="pf-detail-section">
                <div class="pf-detail-section-heading"><div><h2>{{ __('public.detail.categories') }}</h2><p>{{ trans_choice('public.category_count', $competition->categories->count(), ['count' => $competition->categories->count()]) }}</p></div></div>
                <div class="pf-category-list">
                    @foreach($competition->categories as $category)
                        <details @if($loop->first) open @endif>
                            <summary><span><strong>{{ $category->name }}</strong><small>{{ trans_choice('public.detail.photo_limit', $category->max_photos_per_participant, ['count' => $category->max_photos_per_participant]) }}</small></span><x-public.icon name="arrow-right" /></summary>
                            <dl>
                                @if($category->genders->isNotEmpty())<div><dt>{{ __('public.detail.gender') }}</dt><dd>{{ $category->genders->pluck('name')->filter()->join(', ') }}</dd></div>@endif
                                @if($category->ageEligibilityRule)<div><dt>{{ __('public.detail.age_rule') }}</dt><dd>{{ $category->ageEligibilityRule->name }}@if($category->ageEligibilityRule->description)<small>{{ $category->ageEligibilityRule->description }}</small>@endif</dd></div>@endif
                                @if($category->memberGroups->isNotEmpty())<div><dt>{{ __('public.detail.member_groups') }}</dt><dd>{{ $category->memberGroups->pluck('name')->filter()->join(', ') }}</dd></div>@endif
                                @if($category->captureDevices->isNotEmpty())<div><dt>{{ __('public.detail.capture_devices') }}</dt><dd>{{ $category->captureDevices->pluck('name')->filter()->join(', ') }}</dd></div>@endif
                                @if($category->processingMethods->isNotEmpty())<div><dt>{{ __('public.detail.processing_methods') }}</dt><dd>{{ $category->processingMethods->pluck('name')->filter()->join(', ') }}</dd></div>@endif
                            </dl>
                        </details>
                    @endforeach
                </div>
            </section>

            <section class="pf-detail-section">
                <div class="pf-detail-section-heading"><div><h2>{{ __('public.detail.regulation') }}</h2><p>{{ __('public.detail.regulation_intro') }}</p></div><span class="pf-document-icon"><x-public.icon name="document" /></span></div>
                @if($regulation !== [])
                    <div class="pf-regulation">
                        @foreach($regulation as $sectionIndex => $section)
                            <details @if($loop->first) open @endif>
                                <summary><span>{{ str_pad((string)($sectionIndex + 1), 2, '0', STR_PAD_LEFT) }}</span><strong>{{ $section['title'] }}</strong><x-public.icon name="arrow-right" /></summary>
                                <ol>@foreach($section['items'] as $itemIndex => $item)<li><span>{{ $sectionIndex + 1 }}.{{ $itemIndex + 1 }}</span><p>{{ $item['content'] }}</p></li>@endforeach</ol>
                            </details>
                        @endforeach
                    </div>
                @else
                    <div class="pf-inline-empty">{{ __('public.detail.regulation_empty') }}</div>
                @endif
            </section>
        </div>

        <aside class="pf-detail-aside">
            <div class="pf-action-panel">
                <span class="pf-phase is-{{ $phaseKey }}">{{ __('public.phases.'.$phaseKey) }}</span>
                <h2>{{ __('public.detail.entry_title') }}</h2>
                <p>{{ $isExternal ? __('public.detail.external_entry_hint') : __('public.detail.member_entry_hint') }}</p>
                @if($entryUrl)
                    <a class="pf-button" href="{{ $entryUrl }}" @if($isExternal) target="_blank" rel="noopener noreferrer" @endif>
                        {{ $phaseKey === 'applications_open' ? __('public.detail.enter') : __('public.detail.member_view') }}<x-public.icon name="arrow-right" />
                    </a>
                @endif
                @if($hasResults)<a class="pf-button is-results" href="{{ route('result.competitions.show', $competition) }}"><x-public.icon name="results" />{{ __('public.view_results') }}</a>@endif
            </div>
            <div class="pf-organizer-panel">
                <h2>{{ __('public.detail.organizer') }}</h2>
                <strong>{{ $competition->institution?->name }}</strong>
                @if($competition->institution?->website)<a href="{{ $competition->institution->website }}" target="_blank" rel="noopener noreferrer">{{ __('public.detail.website') }}</a>@endif
                <dl><div><dt>{{ __('public.detail.competition_type') }}</dt><dd>{{ $competition->competitionType?->name }}</dd></div><div><dt>{{ __('public.detail.audience') }}</dt><dd>{{ __('public.audience.'.$audience) }}</dd></div><div><dt>{{ __('public.detail.infrastructure') }}</dt><dd>{{ __('public.infrastructure.'.$infrastructure) }}</dd></div></dl>
            </div>
        </aside>
    </div>
</x-public.layout>
