<x-juri.app-layout :title="$competition->name ?: __('juri.invitation.unnamed_competition')">
    @php
        $snapshotContent = $regulationSnapshot?->content ?? [];
        $regulationLocales = array_keys($snapshotContent);
        $initialLocale = in_array(app()->getLocale(), $regulationLocales, true)
            ? app()->getLocale()
            : ($regulationLocales[0] ?? 'tr');
    @endphp

    <div class="jp-detail-page">
    <a class="jp-back-link" href="{{ route('juri.assignments.index') }}">
        <span aria-hidden="true">←</span> {{ __('juri.assignments.back_to_list') }}
    </a>

    <header class="jp-detail-hero">
        <div class="jp-detail-title">
            <p>{{ $competition->institution->name }}</p>
            <h1>{{ $competition->name ?: __('juri.invitation.unnamed_competition') }}</h1>
        </div>
        <span class="ip-badge {{ $competition->status->badgeClass() }}">
            {{ __('juri.assignments.status.'.$competition->status->value) }}
        </span>
    </header>

    <div class="jp-readonly-note" role="note">
        <x-juri.icon name="info" />
        <p><strong>{{ __('juri.assignments.read_only_title') }}</strong> {{ __('juri.assignments.read_only_text') }}</p>
    </div>

    @php
        $finalSession = $competition->evaluationRounds->firstWhere('is_final', true)?->jurySession;
        $myAttendance = $finalSession?->attendances->firstWhere('juror_id', auth('juri')->id());
    @endphp
    @if($myAttendance)
        <section class="jp-detail-section" aria-labelledby="final-session-title" style="margin-bottom:1.5rem;">
            <header class="jp-detail-section-heading"><div><span>Final turu</span><h2 id="final-session-title">Kurul oturumu ve çıkar çatışması beyanı</h2></div><strong>{{ ['planned'=>'Planlandı','open'=>'Açık','closed'=>'Kapalı'][$finalSession->status] }}</strong></header>
            <dl class="jp-schedule-list"><div><dt>Toplantı zamanı</dt><dd>{{ $finalSession->scheduled_at?->format('d.m.Y H:i') ?: 'Henüz belirlenmedi' }}</dd></div><div><dt>Toplantı yeri</dt><dd>{{ $finalSession->location ?: 'Henüz belirlenmedi' }}</dd></div></dl>
            <form method="POST" action="{{ route('juri.sessions.declaration', $competition) }}" style="margin-top:1rem;">@csrf
                <label class="ia-label" for="conflict_declared">Bu yarışmada tarafsızlığımı etkileyebilecek bir çıkar çatışması</label>
                <select class="ia-input" id="conflict_declared" name="conflict_declared" required><option value="0" @selected(!$myAttendance->conflict_declared)>Bulunmuyor</option><option value="1" @selected($myAttendance->conflict_declared)>Bulunuyor</option></select>
                <label class="ia-label" for="conflict_note" style="margin-top:.8rem;">Beyan açıklaması</label>
                <textarea class="ia-input" id="conflict_note" name="conflict_note" maxlength="2000">{{ old('conflict_note', $myAttendance->conflict_note) }}</textarea>
                <button class="ia-btn" style="margin-top:.8rem;">Beyanı kaydet</button>
            </form>
        </section>
    @endif

    <section class="jp-detail-facts" aria-label="{{ __('juri.assignments.overview') }}">
        <dl>
            <div>
                <dt>{{ __('juri.assignments.audience') }}</dt>
                <dd>{{ $competition->audience ? __('juri.assignments.audience_values.'.$competition->audience->value) : __('juri.assignments.date_not_set') }}</dd>
            </div>
            <div>
                <dt>{{ __('juri.assignments.competition_type') }}</dt>
                <dd>{{ $competition->competitionType?->name ?: __('juri.assignments.date_not_set') }}</dd>
            </div>
            <div>
                <dt>{{ __('juri.assignments.infrastructure') }}</dt>
                <dd>{{ $competition->infrastructure_provider ? __('juri.assignments.infrastructure_values.'.$competition->infrastructure_provider->value) : __('juri.assignments.date_not_set') }}</dd>
            </div>
            <div>
                <dt>{{ __('juri.assignments.categories') }}</dt>
                <dd>{{ trans_choice('juri.assignments.assigned_category_count', $competition->categories->count(), ['count' => $competition->categories->count()]) }}</dd>
            </div>
        </dl>
    </section>

    @if ($competition->subject || $competition->purpose)
        <section class="jp-detail-section" aria-labelledby="competition-brief-title">
            <header class="jp-detail-section-heading">
                <div>
                    <span>{{ __('juri.assignments.competition_information') }}</span>
                    <h2 id="competition-brief-title">{{ __('juri.assignments.subject_and_purpose') }}</h2>
                </div>
            </header>
            <dl class="jp-prose-list">
                @if ($competition->subject)
                    <div><dt>{{ __('juri.assignments.subject') }}</dt><dd>{{ $competition->subject }}</dd></div>
                @endif
                @if ($competition->purpose)
                    <div><dt>{{ __('juri.assignments.purpose') }}</dt><dd>{{ $competition->purpose }}</dd></div>
                @endif
            </dl>
        </section>
    @endif

    <div class="jp-detail-grid">
        <section class="jp-detail-section" aria-labelledby="assigned-categories-title">
            <header class="jp-detail-section-heading">
                <div>
                    <span>{{ __('juri.assignments.assignment_scope') }}</span>
                    <h2 id="assigned-categories-title">{{ __('juri.assignments.assigned_categories') }}</h2>
                </div>
                <strong>{{ $competition->categories->count() }}</strong>
            </header>

            <div class="jp-category-detail-list">
                @foreach ($competition->categories as $category)
                    <article class="jp-category-detail">
                        <h3>{{ $category->name ?: __('juri.assignments.unnamed_category') }}</h3>
                        @if(in_array($operationalPhase->value, ['evaluation_open', 'evaluation_closed', 'results_published'], true))
                            <a class="ia-btn" style="margin:.75rem 0;" href="{{ route('juri.evaluations.show', [$competition, $category]) }}">{{ __('juri.evaluation.open') }}</a>
                        @endif
                        <div class="jp-award-list">
                            <h4>{{ __('juri.assignments.awards') }}</h4>
                            @forelse ($category->awards as $award)
                                <div class="jp-award-row">
                                    <div>
                                        <strong>{{ $award->awardReference?->name ?: __('juri.assignments.unnamed_award') }}</strong>
                                        @if ($award->special_award_text)
                                            <p>{{ $award->special_award_text }}</p>
                                        @endif
                                        @if ($award->material_award)
                                            <p>{{ __('juri.assignments.material_award') }}: {{ $award->material_award }}</p>
                                        @endif
                                    </div>
                                    <span>{{ __('juri.assignments.quantity', ['count' => $award->quantity]) }}</span>
                                </div>
                            @empty
                                <p class="jp-inline-empty">{{ __('juri.assignments.no_awards') }}</p>
                            @endforelse
                        </div>
                        <div class="jp-criterion-list">
                            <h4>{{ __('juri.assignments.evaluation_criteria') }}</h4>
                            @forelse ($category->evaluationCriteria as $criterionAssignment)
                                <div class="jp-criterion-row">
                                    <div>
                                        <strong>{{ $criterionAssignment->criterion?->name ?: __('juri.assignments.unnamed_criterion') }}</strong>
                                        @if ($criterionAssignment->criterion?->description)
                                            <p>{{ $criterionAssignment->criterion->description }}</p>
                                        @endif
                                    </div>
                                    <dl>
                                        <div><dt>{{ __('juri.assignments.score_range') }}</dt><dd>{{ $criterionAssignment->min_score }}–{{ $criterionAssignment->max_score }}</dd></div>
                                        <div><dt>{{ __('juri.assignments.relative_weight') }}</dt><dd>{{ rtrim(rtrim($criterionAssignment->weight, '0'), '.') }}</dd></div>
                                    </dl>
                                </div>
                            @empty
                                <p class="jp-inline-empty">{{ __('juri.assignments.no_evaluation_criteria') }}</p>
                            @endforelse
                        </div>
                    </article>
                @endforeach
            </div>
        </section>

        <div class="jp-detail-aside">
            <section class="jp-detail-section" aria-labelledby="schedule-title">
                <header class="jp-detail-section-heading">
                    <div>
                        <span>{{ __('juri.assignments.timeline') }}</span>
                        <h2 id="schedule-title">{{ __('juri.assignments.schedule') }}</h2>
                    </div>
                </header>
                <dl class="jp-schedule-list">
                    @foreach (['application_starts_at' => 'application_starts', 'application_ends_at' => 'application_ends', 'competition_ends_at' => 'competition_ends'] as $field => $label)
                        <div>
                            <dt>{{ __('juri.assignments.dates.'.$label) }}</dt>
                            <dd>
                                @if ($competition->{$field})
                                    <time datetime="{{ $competition->{$field}->toIso8601String() }}">{{ $competition->{$field}->format('d.m.Y H:i') }}</time>
                                @else
                                    {{ __('juri.assignments.date_not_set') }}
                                @endif
                            </dd>
                        </div>
                    @endforeach
                </dl>
            </section>

            @if ($competition->captureRegions->isNotEmpty())
                <section class="jp-detail-section" aria-labelledby="regions-title">
                    <header class="jp-detail-section-heading">
                        <div>
                            <span>{{ __('juri.assignments.marathon_settings') }}</span>
                            <h2 id="regions-title">{{ __('juri.assignments.capture_regions') }}</h2>
                        </div>
                    </header>
                    <ul class="jp-region-list">
                        @foreach ($competition->captureRegions as $region)
                            <li>
                                <span aria-hidden="true"></span>
                                {{ $region->country?->official_name ?: __('juri.assignments.date_not_set') }}
                                @if ($region->city?->official_name)
                                    / {{ $region->city->official_name }}
                                @endif
                            </li>
                        @endforeach
                    </ul>
                    <p class="jp-region-note">{{ __('juri.assignments.capture_regions_hint') }}</p>
                </section>
            @endif
        </div>
    </div>

    <section class="jp-detail-section jp-regulation" aria-labelledby="regulation-title" x-data="{ locale: @js($initialLocale) }">
        <header class="jp-detail-section-heading jp-regulation-heading">
            <div>
                <span>{{ __('juri.assignments.official_document') }}</span>
                <h2 id="regulation-title">{{ __('juri.assignments.regulation') }}</h2>
                <p>{{ __('juri.assignments.regulation_hint') }}</p>
            </div>
            @if ($regulationSnapshot)
                <small>{{ __('juri.assignments.regulation_snapshot', ['version' => $regulationSnapshot->version, 'date' => $regulationSnapshot->compiled_at?->format('d.m.Y H:i')]) }}</small>
            @endif
        </header>

        @if ($regulationSnapshot && $regulationLocales !== [])
            @if (count($regulationLocales) > 1)
                <div class="jp-language-tabs" role="tablist" aria-label="{{ __('juri.assignments.language_tabs') }}">
                    @foreach ($regulationLocales as $locale)
                        <button
                            type="button"
                            :class="{ 'is-active': locale === @js($locale) }"
                            @click="locale = @js($locale)"
                            role="tab"
                            :aria-selected="(locale === @js($locale)).toString()"
                            aria-controls="jury-regulation-{{ $locale }}"
                        >{{ config('locales.supported.'.$locale, strtoupper($locale)) }}</button>
                    @endforeach
                </div>
            @endif

            @foreach ($snapshotContent as $locale => $sections)
                <article
                    id="jury-regulation-{{ $locale }}"
                    class="jp-regulation-document"
                    x-show="locale === @js($locale)"
                    @if ($locale !== $initialLocale) x-cloak @endif
                    role="tabpanel"
                    lang="{{ $locale }}"
                >
                    @foreach ($sections as $sectionIndex => $section)
                        <section>
                            <h3><span>{{ str_pad((string) ($sectionIndex + 1), 2, '0', STR_PAD_LEFT) }}</span>{{ $section['title'] }}</h3>
                            <ol role="list">
                                @foreach ($section['items'] as $itemIndex => $item)
                                    <li role="listitem">
                                        <span>{{ $sectionIndex + 1 }}.{{ $itemIndex + 1 }}</span>
                                        <p>{{ $item['content'] }}</p>
                                    </li>
                                @endforeach
                            </ol>
                        </section>
                    @endforeach
                </article>
            @endforeach
        @else
            <div class="jp-regulation-empty">
                <strong>{{ __('juri.assignments.regulation_unavailable_title') }}</strong>
                <p>{{ __('juri.assignments.regulation_unavailable_text') }}</p>
            </div>
        @endif
    </section>
    </div>
</x-juri.app-layout>
