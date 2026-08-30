<x-eys.app-layout :title="__('eys.competitions.title')">
    <div class="ip-panel-stack">
        <x-eys.breadcrumb :crumbs="[
            ['label' => __('eys.nav.dashboard'), 'url' => route('eys.dashboard')],
            ['label' => __('eys.module_names.Institution'), 'url' => route('eys.institution.dashboard')],
            ['label' => __('eys.competitions.title'), 'url' => route('eys.competitions.index')],
            ['label' => $competition->name ?: __('eys.competitions.untitled')],
        ]" />

        <div class="ip-card">
            <div class="ip-section-title">{{ $competition->name ?: __('eys.competitions.untitled') }}</div>
            <div class="ip-section-hint">
                {{ $competition->institution?->name }}
                &middot;
                <span class="ip-badge {{ $competition->status->badgeClass() }}">
                    {{ __('eys.competitions.status.'.$competition->status->value) }}
                </span>
            </div>

            @if ($competition->latest_review_message)
                <div class="ip-alert ip-alert-warning">
                    <x-eys.icon name="warning" />
                    <div>
                        <div class="ip-alert-title">{{ __('eys.competitions.latest_message_title') }}</div>
                        <div class="ip-alert-text">{{ $competition->latest_review_message }}</div>
                    </div>
                </div>
            @endif
        </div>

        @foreach ($steps as $number => $stepDef)
            @if (! in_array($number, [9, 11], true) && $stepDef->isApplicable($competition) && $stepDef->isImplemented())
                <section class="ip-card" id="competition-step-{{ $number }}">
                    <div class="ip-section-title">{{ $stepDef->label() }}</div>
                    @foreach ($stepDef->data($competition) as $field => $fieldValue)
                        @if ($field === 'categories' && $number === 6)
                            @foreach ($competition->categories as $category)
                                <div class="ia-field" style="padding: 1rem 0; border-bottom: 1px solid var(--ia-surface-border);">
                                    <strong style="color: var(--ia-cream);">{{ $category->getTranslation('tr', false)?->name ?: '—' }}</strong>
                                    @if ($category->getTranslation('en', false)?->name)<span style="color: var(--ia-muted);"> / {{ $category->getTranslation('en', false)?->name }}</span>@endif
                                    <div style="margin-top: .65rem; color: var(--ia-muted); font-size: .82rem; line-height: 1.7;">
                                        <div><b>{{ __('eys.competitions.fields.genders') }}:</b> {{ $category->genders->pluck('name')->join(', ') }}</div>
                                        <div><b>{{ __('eys.competitions.fields.birth_date') }}:</b> {{ $category->ageEligibilityRule?->name ?: '—' }}</div>
                                        <div><b>{{ __('eys.competitions.fields.member_groups') }}:</b> {{ $category->memberGroups->pluck('name')->join(', ') }}</div>
                                        <div><b>{{ __('eys.competitions.fields.capture_devices') }}:</b> {{ $category->captureDevices->pluck('name')->join(', ') }}</div>
                                        <div><b>{{ __('eys.competitions.fields.processing_methods') }}:</b> {{ $category->processingMethods->pluck('name')->join(', ') }}</div>
                                    </div>
                                </div>
                            @endforeach
                        @elseif ($field === 'categories' && $number === 7)
                            @foreach ($competition->categories as $category)
                                <div class="ia-field" style="padding: 1rem 0; border-bottom: 1px solid var(--ia-surface-border);">
                                    <strong style="color: var(--ia-cream);">{{ $category->getTranslation('tr', false)?->name ?: '—' }}</strong>
                                    @if ($category->getTranslation('en', false)?->name)<span style="color: var(--ia-muted);"> / {{ $category->getTranslation('en', false)?->name }}</span>@endif

                                    @forelse ($category->awards as $award)
                                        <div style="margin-top: .75rem; padding: .75rem; border: 1px solid var(--ia-surface-border); border-radius: .65rem; color: var(--ia-muted); font-size: .82rem; line-height: 1.7;">
                                            <div style="color: var(--ia-cream); font-weight: 700;">
                                                {{ $award->awardReference?->name ?: '—' }}
                                                <span style="color: var(--ia-muted); font-weight: 400;">&middot; {{ __('eys.competitions.fields.award_quantity') }}: {{ $award->quantity }}</span>
                                            </div>
                                            @foreach (['tr', 'en'] as $locale)
                                                @php
                                                    $translation = $award->getTranslation($locale, false);
                                                @endphp
                                                @if ($translation?->special_award_text || $translation?->material_award)
                                                    <div style="margin-top: .35rem;">
                                                        <b>{{ strtoupper($locale) }}</b>
                                                        @if ($translation?->special_award_text)
                                                            &middot; {{ __('eys.competitions.fields.special_award_text') }}: {{ $translation->special_award_text }}
                                                        @endif
                                                        @if ($translation?->material_award)
                                                            &middot; {{ __('eys.competitions.fields.material_award') }}: {{ $translation->material_award }}
                                                        @endif
                                                    </div>
                                                @endif
                                            @endforeach
                                        </div>
                                    @empty
                                        <div style="margin-top: .65rem; color: var(--ia-muted);">{{ __('eys.competitions.no_category_awards') }}</div>
                                    @endforelse
                                </div>
                            @endforeach
                        @elseif ($field === 'categories' && $number === 8)
                            @foreach ($competition->categories as $category)
                                <div class="ia-field" style="padding: 1rem 0; border-bottom: 1px solid var(--ia-surface-border);">
                                    <strong style="color: var(--ia-cream);">{{ $category->getTranslation('tr', false)?->name ?: '—' }}</strong>
                                    @if ($category->getTranslation('en', false)?->name)<span style="color: var(--ia-muted);"> / {{ $category->getTranslation('en', false)?->name }}</span>@endif

                                    @forelse ($category->jurorAssignments as $assignment)
                                        @php
                                            $reviewJuror = $assignment->juror;
                                            $reviewInvitation = $assignment->invitation;
                                            $reviewInvitationStatus = $reviewInvitation?->status();
                                        @endphp
                                        <div style="display: flex; align-items: center; flex-wrap: wrap; gap: .55rem; margin-top: .75rem; padding: .75rem; border: 1px solid var(--ia-surface-border); border-radius: .65rem; color: var(--ia-muted); font-size: .82rem;">
                                            <strong style="color: var(--ia-cream);">
                                                {{ $reviewJuror ? trim($reviewJuror->first_name.' '.$reviewJuror->last_name) : trim(($reviewInvitation?->first_name ?? '').' '.($reviewInvitation?->last_name ?? '')) }}
                                            </strong>
                                            <span>&middot; {{ $reviewJuror?->email ?: $reviewInvitation?->email }}</span>
                                            <span class="ip-badge {{ $reviewJuror ? 'is-active' : $reviewInvitationStatus?->badgeClass() }}">
                                                {{ $reviewJuror ? __('eys.competitions.jury_registered') : __('eys.competitions.jury_invitation_status.'.$reviewInvitationStatus?->value) }}
                                            </span>
                                            @if ($reviewInvitation?->sent_at)
                                                <span>&middot; {{ __('eys.competitions.jury_last_sent', ['date' => $reviewInvitation->sent_at->format('d.m.Y H:i'), 'count' => $reviewInvitation->send_count]) }}</span>
                                            @endif
                                            @if ($reviewInvitation?->expires_at)
                                                <span>&middot; {{ __('eys.competitions.jury_expires_at', ['date' => $reviewInvitation->expires_at->format('d.m.Y H:i')]) }}</span>
                                            @endif
                                        </div>
                                    @empty
                                        <div style="margin-top: .65rem; color: var(--ia-muted);">{{ __('eys.competitions.no_category_jurors') }}</div>
                                    @endforelse

                                    <div style="margin-top: 1rem; color: var(--ia-muted-dim); font-size: .72rem; font-weight: 700; letter-spacing: .04em; text-transform: uppercase;">{{ __('eys.competitions.fields.evaluation_criteria') }}</div>
                                    @forelse ($category->evaluationCriteria as $criterionAssignment)
                                        <div style="display: grid; grid-template-columns: minmax(0, 1fr) auto auto; align-items: center; gap: 1rem; margin-top: .55rem; padding: .75rem; border: 1px solid var(--ia-surface-border); border-radius: .65rem; color: var(--ia-muted); font-size: .82rem;">
                                            <span><strong style="color: var(--ia-cream);">{{ $criterionAssignment->criterion?->name ?: '—' }}</strong>@if ($criterionAssignment->criterion?->description)<small style="display:block; margin-top:.2rem; color:var(--ia-muted-dim);">{{ $criterionAssignment->criterion->description }}</small>@endif</span>
                                            <span>{{ __('eys.competitions.fields.score_range') }}: <strong style="color: var(--ia-cream);">{{ $criterionAssignment->min_score }}–{{ $criterionAssignment->max_score }}</strong></span>
                                            <span>{{ __('eys.competitions.fields.relative_weight') }}: <strong style="color: var(--ia-cream);">{{ rtrim(rtrim($criterionAssignment->weight, '0'), '.') }}</strong></span>
                                        </div>
                                    @empty
                                        <div style="margin-top: .55rem; color: var(--ia-muted);">{{ __('eys.competitions.no_category_evaluation_criteria') }}</div>
                                    @endforelse
                                </div>
                            @endforeach
                        @elseif ($field === 'regions')
                            <div class="ia-field">
                                <x-eys.label :value="__('eys.competitions.fields.capture_regions')" />
                                @php
                                    $reviewRegions = $competition->captureRegions->isNotEmpty()
                                        ? $competition->captureRegions
                                        : collect([$competition]);
                                @endphp
                                @foreach ($reviewRegions as $region)
                                    <div style="margin-top: .5rem; color: var(--ia-cream);">
                                        {{ $region->country?->official_name ?: '—' }} / {{ $region->city?->official_name ?: '—' }}
                                    </div>
                                @endforeach
                            </div>
                        @elseif ($field === 'regulation_inputs')
                            <div class="ia-field">
                                <x-eys.label :value="__('eys.competitions.fields.regulation_inputs')" />
                                @forelse ($competition->regulationInputs as $input)
                                    <div style="margin-top: .5rem; color: var(--ia-cream); white-space: pre-line;">{{ strtoupper($input->locale) }} — {{ $input->content ?: '—' }}</div>
                                @empty
                                    <div style="color: var(--ia-cream);">—</div>
                                @endforelse
                            </div>
                        @elseif (is_array($fieldValue))
                            <div class="ia-field">
                                <x-eys.label :value="config('locales.supported.'.$field, strtoupper($field))" />
                                @foreach ($fieldValue as $translatedField => $translatedValue)
                                    <div style="margin-top: .75rem;">
                                        <div style="font-size: .76rem; color: var(--ia-muted-dim); margin-bottom: .25rem;">{{ __('eys.competitions.fields.'.$translatedField) }}</div>
                                        <div style="color: var(--ia-cream); white-space: pre-line;">{{ $translatedValue ?: '—' }}</div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            @php
                                $translationKey = 'eys.competitions.field_values.'.$field.'.'.$fieldValue;
                                $displayValue = match (true) {
                                    $field === 'competition_type' => $competition->competitionType?->name,
                                    $field === 'country' => $competition->country?->official_name,
                                    $field === 'city' => $competition->city?->official_name,
                                    $field === 'participant_approval_process' => $competition->participantApprovalProcess?->name,
                                    $fieldValue && trans()->has($translationKey) => __($translationKey),
                                    default => $fieldValue,
                                };
                            @endphp
                            <div class="ia-field">
                                <x-eys.label :value="__('eys.competitions.fields.'.$field)" />
                                <div style="color: var(--ia-cream); white-space: pre-line;">{{ $displayValue ?: '—' }}</div>
                            </div>
                        @endif
                    @endforeach
                </section>
            @endif
        @endforeach

        <div class="ip-card">
            <div class="ip-section-title">{{ __('eys.competitions.regulation_title') }}</div>
            <div class="ip-section-hint">
                {{ $regulationSnapshot
                    ? __('eys.competitions.regulation_snapshot', ['version' => $regulationSnapshot->version, 'date' => $regulationSnapshot->compiled_at->format('d.m.Y H:i')])
                    : __('eys.competitions.regulation_live') }}
            </div>
            @foreach ($compiledRegulation as $locale => $sections)
                <section style="margin-top: 1.25rem; padding-top: 1rem; border-top: 1px solid var(--ia-surface-border);">
                    <strong style="color: var(--ia-copper-bright);">{{ strtoupper($locale) }}</strong>
                    @foreach ($sections as $sectionIndex => $section)
                        <h3 style="margin: 1.1rem 0 .6rem; color: var(--ia-cream); font-size: .9rem;">{{ $sectionIndex + 1 }}. {{ $section['title'] }}</h3>
                        @foreach ($section['items'] as $itemIndex => $item)
                            <p style="display: grid; grid-template-columns: 2.7rem 1fr; gap: .6rem; margin: .5rem 0; color: var(--ia-muted); font-size: .8rem; line-height: 1.65;"><span style="color: var(--ia-muted-dim);">{{ $sectionIndex + 1 }}.{{ $itemIndex + 1 }}</span><span>{{ $item['content'] }}</span></p>
                        @endforeach
                    @endforeach
                </section>
            @endforeach
        </div>

        @include('eys.competitions._review-panel')

        <div class="ip-card">
            <div class="ip-section-title">{{ __('eys.competitions.history_title') }}</div>

            @forelse ($competition->statusLogs as $log)
                <div style="padding: .85rem 0; border-bottom: 1px solid var(--ia-surface-border);">
                    <div style="display: flex; justify-content: space-between; gap: 1rem; font-size: .85rem;">
                        <strong style="color: var(--ia-cream);">{{ __('eys.competitions.log_actions.'.$log->action) }}</strong>
                        <span style="color: var(--ia-muted-dim);">{{ $log->created_at->format('d.m.Y H:i') }}</span>
                    </div>
                    @if ($log->message)
                        <div style="font-size: .85rem; color: var(--ia-muted); margin-top: .35rem;">{{ $log->message }}</div>
                    @endif
                    @if ($log->changes)
                        <ul style="font-size: .82rem; color: var(--ia-muted); margin-top: .35rem; padding-left: 1.1rem;">
                            @foreach ($log->changes as $field => $diff)
                                @php
                                    $fieldParts = explode('.', $field, 2);
                                    $fieldLabel = count($fieldParts) === 2
                                        ? config('locales.supported.'.$fieldParts[0], strtoupper($fieldParts[0])).' / '.__('eys.competitions.fields.'.$fieldParts[1])
                                        : __('eys.competitions.fields.'.$field);
                                @endphp
                                <li>
                                    <strong>{{ $fieldLabel }}:</strong>
                                    <span style="color: #e0857a;">{{ $diff[0] ?: '—' }}</span>
                                    →
                                    <span style="color: #8fcf93;">{{ $diff[1] ?: '—' }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            @empty
                <div class="ip-table-empty">{{ __('eys.competitions.no_history') }}</div>
            @endforelse
        </div>
    </div>
</x-eys.app-layout>
