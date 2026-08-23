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
            @if ($stepDef->isApplicable($competition) && $stepDef->isImplemented())
                <div class="ip-card">
                    <div class="ip-section-title">{{ $stepDef->label() }}</div>
                    @foreach ($stepDef->data($competition) as $field => $fieldValue)
                        @if ($field === 'categories')
                            @foreach ($competition->categories as $category)
                                <div class="ia-field" style="padding: 1rem 0; border-bottom: 1px solid var(--ia-surface-border);">
                                    <strong style="color: var(--ia-cream);">{{ $category->getTranslation('tr', false)?->name ?: '—' }}</strong>
                                    @if ($category->getTranslation('en', false)?->name)<span style="color: var(--ia-muted);"> / {{ $category->getTranslation('en', false)?->name }}</span>@endif
                                    <div style="margin-top: .65rem; color: var(--ia-muted); font-size: .82rem; line-height: 1.7;">
                                        <div><b>{{ __('eys.competitions.fields.genders') }}:</b> {{ $category->genders->pluck('name')->join(', ') }}</div>
                                        <div><b>{{ __('eys.competitions.fields.birth_date') }}:</b> {{ $category->ageEligibilityRule?->name ?: '—' }}</div>
                                        <div><b>{{ __('eys.competitions.fields.member_groups') }}:</b> {{ $category->memberGroups->pluck('name')->join(', ') }}</div>
                                        <div><b>{{ __('eys.competitions.fields.capture_devices') }}:</b> {{ $category->captureDevices->pluck('name')->join(', ') }}</div>
                                    </div>
                                </div>
                            @endforeach
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

                <div style="display: flex; gap: .75rem; margin-bottom: 1.5rem;">
                    <form method="POST" action="{{ route('eys.competitions.approve', $competition) }}">
                        @csrf
                        <button type="button" class="ia-btn" onclick="eysConfirm(@js(__('eys.competitions.confirm_approve')), this.closest('form'))">{{ __('eys.competitions.action_approve') }}</button>
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
