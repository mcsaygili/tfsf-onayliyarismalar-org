<x-institution.app-layout :title="__('institution.nav.competitions')">
    @include('institution.competitions._steps')

    @php
        $summary = __('institution.competitions.summary');
        $applicableChecks = collect($submissionChecks)->where('status', '!=', 'not_applicable');
        $completeCheckCount = $applicableChecks->where('status', 'complete')->count();
        $locales = $competition->requiresEnglishContent() ? ['tr', 'en'] : ['tr'];
        $notProvided = $summary['not_provided'];
        $formatDate = fn ($date) => $date?->format('d.m.Y H:i') ?: $notProvided;
        $stepUrl = fn (int $number) => route('institution.competitions.step.show', [$competition, $number]);
    @endphp

    <main class="ip-summary-stage" x-data="{ locale: 'tr', confirmed: false }">
        <section class="ip-summary-hero" aria-labelledby="application-summary-title">
            <div>
                <span class="ip-summary-eyebrow">{{ __('institution.competitions.steps.11.label') }}</span>
                <h1 id="application-summary-title">{{ $summary['title'] }}</h1>
                <p>{{ $summary['hint'] }}</p>
            </div>
            <div class="ip-summary-readiness {{ $submissionReady ? 'is-ready' : 'is-blocked' }}">
                <span class="ip-summary-readiness-mark" aria-hidden="true">{{ $submissionReady ? '✓' : '!' }}</span>
                <span>
                    <strong>{{ $submissionReady ? $summary['ready'] : $summary['not_ready'] }}</strong>
                    <small>{{ __('institution.competitions.summary.check_progress', ['complete' => $completeCheckCount, 'total' => $applicableChecks->count()]) }}</small>
                    @unless ($submissionReady)
                        <small>{{ __('institution.competitions.summary.blocking_count', ['count' => count($submissionBlockers)]) }}</small>
                    @endunless
                </span>
            </div>
        </section>

        <ol class="ip-summary-checks" aria-label="{{ $summary['title'] }}">
            @foreach ($submissionChecks as $check)
                <li class="is-{{ $check['status'] }}">
                    <span>{{ $check['number'] }}</span>
                    <strong>{{ $check['label'] }}</strong>
                    <small>{{ data_get($summary, 'statuses.'.$check['status']) }}</small>
                </li>
            @endforeach
        </ol>

        @if (collect($submissionBlockers)->contains('number', 10))
            <div class="ip-alert ip-alert-warning ip-summary-alert" role="status">
                <x-institution.icon name="warning" />
                <div>
                    <div class="ip-alert-title">{{ $summary['pricing_blocker_title'] }}</div>
                    <div class="ip-alert-text">{{ $summary['pricing_blocker_text'] }}</div>
                </div>
            </div>
        @endif

        <div class="ip-alert {{ $pendingJuryAssignments->isEmpty() ? 'ip-summary-alert-success' : 'ip-alert-warning' }} ip-summary-alert" role="status">
            <x-institution.icon name="{{ $pendingJuryAssignments->isEmpty() ? 'check' : 'warning' }}" />
            <div>
                <div class="ip-alert-title">
                    {{ $pendingJuryAssignments->isEmpty()
                        ? $summary['jury_ready_title']
                        : __('institution.competitions.summary.jury_warning_title', ['count' => $pendingJuryAssignments->count()]) }}
                </div>
                <div class="ip-alert-text">{{ $pendingJuryAssignments->isEmpty() ? $summary['jury_ready_text'] : $summary['jury_warning_text'] }}</div>
            </div>
        </div>

        <div class="ip-summary-sections">
            <section class="ip-summary-section" aria-labelledby="summary-audience-title">
                <header>
                    <div><span>01</span><h2 id="summary-audience-title">{{ $summary['section_audience'] }}</h2></div>
                    <a href="{{ $stepUrl(1) }}">{{ $summary['edit'] }} <span aria-hidden="true">→</span></a>
                </header>
                <dl class="ip-summary-definition-grid">
                    <div>
                        <dt>{{ __('institution.competitions.fields.audience') }}</dt>
                        <dd>{{ data_get(__('institution.competitions.audiences'), $competition->audience?->value.'.title', $notProvided) }}</dd>
                    </div>
                    <div>
                        <dt>{{ __('institution.competitions.language_tabs_label') }}</dt>
                        <dd>{{ $competition->requiresEnglishContent() ? __('institution.competitions.language_requirement.international_short') : __('institution.competitions.language_requirement.national_short') }}</dd>
                    </div>
                </dl>
            </section>

            <section class="ip-summary-section" aria-labelledby="summary-information-title">
                <header>
                    <div><span>02</span><h2 id="summary-information-title">{{ $summary['section_information'] }}</h2></div>
                    <a href="{{ $stepUrl(2) }}">{{ $summary['edit'] }} <span aria-hidden="true">→</span></a>
                </header>

                <dl class="ip-summary-definition-grid">
                    <div><dt>{{ __('institution.competitions.fields.organizing_institution') }}</dt><dd>{{ $competition->institution?->name ?: $notProvided }}</dd></div>
                    <div><dt>{{ __('institution.competitions.fields.partners') }}</dt><dd>{{ $competition->partners ?: $notProvided }}</dd></div>
                </dl>

                <div class="ip-summary-language" role="group" aria-label="{{ __('institution.competitions.language_tabs_label') }}">
                    <div class="ip-language-tabs" role="tablist">
                        @foreach ($locales as $locale)
                            <button type="button" class="ip-language-tab" :class="{ 'is-active': locale === '{{ $locale }}' }" @click="locale = '{{ $locale }}'" role="tab" :aria-selected="(locale === '{{ $locale }}').toString()">
                                {{ config('locales.supported.'.$locale, strtoupper($locale)) }}
                            </button>
                        @endforeach
                    </div>
                    @foreach ($locales as $locale)
                        @php($translation = $competition->getTranslation($locale, false))
                        <div class="ip-summary-language-panel" x-show="locale === '{{ $locale }}'" {{ $locale !== 'tr' ? 'x-cloak' : '' }} role="tabpanel">
                            <dl>
                                <div><dt>{{ __('institution.competitions.fields.name') }}</dt><dd>{{ $translation?->name ?: $notProvided }}</dd></div>
                                <div><dt>{{ __('institution.competitions.fields.subject') }}</dt><dd>{{ $translation?->subject ?: $notProvided }}</dd></div>
                                <div><dt>{{ __('institution.competitions.fields.purpose') }}</dt><dd>{{ $translation?->purpose ?: $notProvided }}</dd></div>
                            </dl>
                        </div>
                    @endforeach
                </div>

                <h3 class="ip-summary-subtitle">{{ $summary['calendar'] }}</h3>
                <dl class="ip-summary-definition-grid is-three">
                    <div><dt>{{ __('institution.competitions.fields.application_starts_at') }}</dt><dd>{{ $formatDate($competition->application_starts_at) }}</dd></div>
                    <div><dt>{{ __('institution.competitions.fields.application_ends_at') }}</dt><dd>{{ $formatDate($competition->application_ends_at) }}</dd></div>
                    <div><dt>{{ __('institution.competitions.fields.competition_ends_at') }}</dt><dd>{{ $formatDate($competition->competition_ends_at) }}</dd></div>
                </dl>
            </section>

            <section class="ip-summary-section" aria-labelledby="summary-setup-title">
                <header>
                    <div><span>03–05</span><h2 id="summary-setup-title">{{ $summary['section_setup'] }}</h2></div>
                    <a href="{{ $stepUrl(3) }}">{{ $summary['edit'] }} <span aria-hidden="true">→</span></a>
                </header>
                <dl class="ip-summary-definition-grid">
                    <div><dt>{{ __('institution.competitions.fields.infrastructure_provider') }}</dt><dd>{{ data_get(__('institution.competitions.infrastructure_providers'), $competition->infrastructure_provider?->value.'.title', $notProvided) }}</dd></div>
                    @if ($competition->infrastructure_provider?->value === 'tfsf')
                        <div><dt>{{ __('institution.competitions.fields.competition_type') }}</dt><dd>{{ $competition->competitionType?->name ?: $notProvided }}</dd></div>
                    @else
                        <div><dt>{{ __('institution.competitions.fields.external_provider_name') }}</dt><dd>{{ $competition->external_provider_name ?: $notProvided }}</dd></div>
                        <div><dt>{{ __('institution.competitions.fields.external_entry_url') }}</dt><dd>{{ $competition->external_entry_url ?: $notProvided }}</dd></div>
                    @endif
                    @if ($competition->participantApprovalProcess)
                        <div><dt>{{ __('institution.competitions.fields.participant_approval_process') }}</dt><dd>{{ $competition->participantApprovalProcess->name }}</dd></div>
                    @endif
                </dl>
                @if ($competition->captureRegions->isNotEmpty())
                    <h3 class="ip-summary-subtitle">{{ $summary['capture_regions'] }}</h3>
                    <ul class="ip-summary-inline-list">
                        @foreach ($competition->captureRegions as $region)
                            <li>{{ $region->country?->official_name ?: $notProvided }} / {{ $region->city?->official_name ?: $notProvided }}</li>
                        @endforeach
                    </ul>
                @endif
            </section>

            <section class="ip-summary-section" aria-labelledby="summary-categories-title">
                <header>
                    <div><span>06</span><h2 id="summary-categories-title">{{ $summary['section_categories'] }}</h2></div>
                    <a href="{{ $stepUrl(6) }}">{{ $summary['edit'] }} <span aria-hidden="true">→</span></a>
                </header>
                <div class="ip-summary-category-list">
                    @foreach ($competition->categories as $category)
                        <article>
                            <h3>{{ $category->getTranslation('tr', false)?->name ?: $notProvided }}@if ($competition->requiresEnglishContent()) <small>/ {{ $category->getTranslation('en', false)?->name ?: $notProvided }}</small>@endif</h3>
                            <dl class="ip-summary-definition-grid is-three">
                                <div><dt>{{ __('institution.competitions.fields.birth_date') }}</dt><dd>{{ $category->ageEligibilityRule?->name ?: $notProvided }}</dd></div>
                                <div><dt>{{ __('institution.competitions.fields.genders') }}</dt><dd>{{ $category->genders->pluck('name')->join(', ') ?: $notProvided }}</dd></div>
                                <div><dt>{{ __('institution.competitions.fields.member_groups') }}</dt><dd>{{ $category->memberGroups->pluck('name')->join(', ') ?: $notProvided }}</dd></div>
                                <div><dt>{{ __('institution.competitions.fields.capture_devices') }}</dt><dd>{{ $category->captureDevices->pluck('name')->join(', ') ?: $notProvided }}</dd></div>
                                <div><dt>{{ __('institution.competitions.fields.processing_methods') }}</dt><dd>{{ $category->processingMethods->pluck('name')->join(', ') ?: $notProvided }}</dd></div>
                            </dl>
                        </article>
                    @endforeach
                </div>
            </section>

            <section class="ip-summary-section" aria-labelledby="summary-awards-title">
                <header>
                    <div><span>07</span><h2 id="summary-awards-title">{{ $summary['section_awards'] }}</h2></div>
                    <a href="{{ $stepUrl(7) }}">{{ $summary['edit'] }} <span aria-hidden="true">→</span></a>
                </header>
                <div class="ip-summary-category-list">
                    @foreach ($competition->categories as $category)
                        <article>
                            <h3>{{ $category->name ?: $notProvided }}</h3>
                            @forelse ($category->awards as $award)
                                <div class="ip-summary-person-row">
                                    <span><strong>{{ $award->awardReference?->name ?: $notProvided }}</strong><small>{{ __('institution.competitions.fields.award_quantity') }}: {{ $award->quantity }}</small></span>
                                    @php($awardTranslation = $award->getTranslation(app()->getLocale(), true))
                                    @if ($awardTranslation?->special_award_text || $awardTranslation?->material_award)
                                        <small>{{ collect([$awardTranslation?->special_award_text, $awardTranslation?->material_award])->filter()->join(' · ') }}</small>
                                    @endif
                                </div>
                            @empty
                                <p class="ip-summary-empty">{{ $summary['no_awards'] }}</p>
                            @endforelse
                        </article>
                    @endforeach
                </div>
            </section>

            <section class="ip-summary-section" aria-labelledby="summary-jury-title">
                <header>
                    <div><span>08</span><h2 id="summary-jury-title">{{ $summary['section_jury'] }}</h2></div>
                    <a href="{{ $stepUrl(8) }}">{{ $summary['edit'] }} <span aria-hidden="true">→</span></a>
                </header>
                <div class="ip-summary-category-list">
                    @foreach ($competition->categories as $category)
                        <article>
                            <h3>{{ $category->name ?: $notProvided }}</h3>
                            @forelse ($category->jurorAssignments as $assignment)
                                @php($juror = $assignment->juror)
                                @php($invitation = $assignment->invitation)
                                <div class="ip-summary-person-row">
                                    <span>
                                        <strong>{{ $juror ? trim($juror->first_name.' '.$juror->last_name) : trim(($invitation?->first_name ?? '').' '.($invitation?->last_name ?? '')) }}</strong>
                                        <small>{{ $juror?->email ?: $invitation?->email }}</small>
                                    </span>
                                    <span class="ip-badge {{ $juror ? 'is-active' : 'is-pending' }}">{{ $juror ? __('institution.competitions.jury_status_registered') : __('institution.competitions.jury_status_invited') }}</span>
                                </div>
                            @empty
                                <p class="ip-summary-empty">{{ $summary['no_jurors'] }}</p>
                            @endforelse
                            <h4 class="ip-summary-subtitle">{{ $summary['evaluation_criteria'] }}</h4>
                            @forelse ($category->evaluationCriteria as $criterionAssignment)
                                <div class="ip-summary-person-row">
                                    <span>
                                        <strong>{{ $criterionAssignment->criterion?->name ?: $notProvided }}</strong>
                                        <small>{{ __('institution.competitions.fields.criterion_min_score') }}: {{ $criterionAssignment->min_score }} · {{ __('institution.competitions.fields.criterion_max_score') }}: {{ $criterionAssignment->max_score }}</small>
                                    </span>
                                    <span class="ip-badge is-active">{{ __('institution.competitions.fields.criterion_weight') }}: {{ rtrim(rtrim($criterionAssignment->weight, '0'), '.') }}</span>
                                </div>
                            @empty
                                <p class="ip-summary-empty">{{ $summary['no_evaluation_criteria'] }}</p>
                            @endforelse
                        </article>
                    @endforeach
                </div>
            </section>

            <section class="ip-summary-section" aria-labelledby="summary-regulation-title">
                <header>
                    <div><span>09</span><h2 id="summary-regulation-title">{{ $summary['section_regulation'] }}</h2></div>
                    <a href="{{ $stepUrl(9) }}">{{ $summary['view'] }} <span aria-hidden="true">→</span></a>
                </header>
                <dl class="ip-summary-definition-grid">
                    @foreach ($regulationPreview['content'] as $locale => $sections)
                        <div>
                            <dt>{{ strtoupper($locale) }}</dt>
                            <dd>{{ $summary['regulation_stats'] }}: {{ count($sections) }} / {{ collect($sections)->sum(fn (array $section) => count($section['items'])) }}</dd>
                        </div>
                    @endforeach
                </dl>
            </section>

            <section class="ip-summary-section is-pending" aria-labelledby="summary-payment-title">
                <header>
                    <div><span>10</span><h2 id="summary-payment-title">{{ $summary['section_payment'] }}</h2></div>
                    <span class="ip-badge is-pending">{{ $summary['payment_pending'] }}</span>
                </header>
                <p class="ip-summary-empty">{{ $summary['payment_pending_text'] }}</p>
            </section>
        </div>

        <footer class="ip-summary-submit">
            <a href="{{ $stepUrl(9) }}" class="ia-btn ia-btn-secondary">← {{ $summary['back'] }}</a>
            <div class="ip-summary-submit-control">
                <label class="ip-summary-confirmation {{ $submissionReady ? '' : 'is-disabled' }}">
                    <input type="checkbox" x-model="confirmed" {{ $submissionReady ? '' : 'disabled' }}>
                    <span>{{ $summary['confirmation'] }}</span>
                </label>
                <form method="POST" action="{{ route('institution.competitions.submit', $competition) }}">
                    @csrf
                    <button class="ia-btn" type="submit" :disabled="!confirmed" {{ $submissionReady ? '' : 'disabled' }} title="{{ $submissionReady ? $summary['submit'] : $summary['submit_locked'] }}">
                        {{ $summary['submit'] }} →
                    </button>
                </form>
            </div>
        </footer>
    </main>
</x-institution.app-layout>
