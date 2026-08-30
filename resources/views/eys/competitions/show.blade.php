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
            @if ($number !== 10 && $stepDef->isApplicable($competition) && $stepDef->isImplemented())
                <div class="ip-card">
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
                                        @endphp
                                        <div style="display: flex; align-items: center; flex-wrap: wrap; gap: .55rem; margin-top: .75rem; padding: .75rem; border: 1px solid var(--ia-surface-border); border-radius: .65rem; color: var(--ia-muted); font-size: .82rem;">
                                            <strong style="color: var(--ia-cream);">
                                                {{ $reviewJuror ? trim($reviewJuror->first_name.' '.$reviewJuror->last_name) : trim(($reviewInvitation?->first_name ?? '').' '.($reviewInvitation?->last_name ?? '')) }}
                                            </strong>
                                            <span>&middot; {{ $reviewJuror?->email ?: $reviewInvitation?->email }}</span>
                                            <span class="ip-badge {{ $reviewJuror ? 'is-active' : 'is-pending' }}">
                                                {{ $reviewJuror ? __('eys.competitions.jury_registered') : __('eys.competitions.jury_invited') }}
                                            </span>
                                        </div>
                                    @empty
                                        <div style="margin-top: .65rem; color: var(--ia-muted);">{{ __('eys.competitions.no_category_jurors') }}</div>
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
                </div>
            @endif
        @endforeach

        @if ($competition->status->value === 'pending_review')
            <div class="ip-card">
                <div class="ip-section-title">{{ __('eys.competitions.review_title') }}</div>

                @if ($pendingJuryAssignments->isNotEmpty())
                    <div class="ip-alert ip-alert-warning" role="alert">
                        <x-eys.icon name="warning" />
                        <div>
                            <div class="ip-alert-title">{{ __('eys.competitions.jury_approval_blocked_title', ['count' => $pendingJuryAssignments->count()]) }}</div>
                            <div class="ip-alert-text">{{ __('eys.competitions.jury_approval_blocked') }}</div>
                        </div>
                    </div>
                @endif

                <x-eys.input-error :messages="$errors->get('approval')" />

                <div style="display: flex; gap: .75rem; margin-bottom: 1.5rem;">
                    <form method="POST" action="{{ route('eys.competitions.approve', $competition) }}">
                        @csrf
                        <button
                            type="button"
                            class="ia-btn"
                            @if ($pendingJuryAssignments->isEmpty()) onclick="eysConfirm(@js(__('eys.competitions.confirm_approve')), this.closest('form'))" @endif
                            @disabled($pendingJuryAssignments->isNotEmpty())
                            title="{{ $pendingJuryAssignments->isNotEmpty() ? __('eys.competitions.jury_approval_blocked') : __('eys.competitions.action_approve') }}"
                        >{{ __('eys.competitions.action_approve') }}</button>
                    </form>
                </div>

                <div class="ip-grid-2">
                    <form method="POST" action="{{ route('eys.competitions.reject', $competition) }}">
                        @csrf
                        <div class="ia-field">
                            <x-eys.label :value="__('eys.competitions.action_reject')" />
                            <textarea name="message" class="ia-input" rows="3" placeholder="{{ __('eys.competitions.message_placeholder') }}" required></textarea>
                            <x-eys.input-error :messages="$errors->get('message')" />
                        </div>
                        <button type="submit" class="ia-btn ia-btn-secondary">{{ __('eys.competitions.action_reject') }}</button>
                    </form>

                    <form method="POST" action="{{ route('eys.competitions.request-info', $competition) }}">
                        @csrf
                        <div class="ia-field">
                            <x-eys.label :value="__('eys.competitions.action_request_info')" />
                            <textarea name="message" class="ia-input" rows="3" placeholder="{{ __('eys.competitions.message_placeholder') }}" required></textarea>
                            <x-eys.input-error :messages="$errors->get('message')" />
                        </div>
                        <button type="submit" class="ia-btn ia-btn-secondary">{{ __('eys.competitions.action_request_info') }}</button>
                    </form>
                </div>
            </div>
        @endif

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
