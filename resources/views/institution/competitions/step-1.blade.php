<x-institution.app-layout :title="__('institution.nav.competitions')">
    @include('institution.competitions._steps')

    @if ($competition->status->value === 'needs_info' && $competition->latest_review_message)
        <div class="ip-alert ip-alert-warning">
            <x-institution.icon name="warning" />
            <div>
                <div class="ip-alert-title">{{ __('institution.competitions.needs_info_title') }}</div>
                <div class="ip-alert-text">{{ $competition->latest_review_message }}</div>
            </div>
        </div>
    @endif

    <form method="POST" action="{{ route('institution.competitions.step.update', [$competition, $step]) }}" novalidate autocomplete="off" data-wizard-form
          x-data="{ audience: @js(old('audience', $competition->audience?->value)), initialAudience: @js($competition->audience?->value) }">
        @csrf
        @method('PUT')

        <div class="ip-wizard-stage">
            <div class="ip-card">
                <div class="ip-section-title">{{ __('institution.competitions.steps.1.label') }}</div>
                <div class="ip-section-hint">{{ __('institution.competitions.steps.1.hint') }}</div>

                <fieldset class="ia-field ip-field-last">
                    <legend class="ip-visually-hidden">{{ __('institution.competitions.fields.audience') }}</legend>

                    <x-institution.field-label
                        for="audience"
                        :group="true"
                        :value="__('institution.competitions.fields.audience')"
                        :description="__('institution.competitions.field_help.audience.description')"
                        :example="__('institution.competitions.field_help.audience.example')"
                    />

                    <div class="ip-choice-options">
                        @foreach (['national', 'international'] as $audience)
                            <label class="ip-choice-option" for="audience_{{ $audience }}">
                                <input
                                    id="audience_{{ $audience }}"
                                    type="radio"
                                    name="audience"
                                    value="{{ $audience }}"
                                    x-model="audience"
                                    @checked(old('audience', $competition->audience?->value) === $audience)
                                    @if ($errors->has('audience')) aria-invalid="true" aria-describedby="audience-error" @endif
                                >
                                <span class="ip-choice-content">
                                    <span class="ip-choice-heading">
                                        <strong>{{ __('institution.competitions.audiences.'.$audience.'.title') }}</strong>
                                        <span class="ip-audience-language">{{ __('institution.competitions.audiences.'.$audience.'.language') }}</span>
                                    </span>
                                    <span class="ip-choice-description">{{ __('institution.competitions.audiences.'.$audience.'.description') }}</span>
                                    <details class="ip-choice-definition">
                                        <summary>{{ __('institution.competitions.audience_definition') }}</summary>
                                        <span>{{ __('institution.competitions.audiences.'.$audience.'.definition') }}</span>
                                    </details>
                                </span>
                            </label>
                        @endforeach
                    </div>

                    <x-institution.input-error id="audience-error" :messages="$errors->get('audience')" />
                </fieldset>
            </div>

            <div class="ip-alert ip-alert-warning" x-show="initialAudience && audience !== initialAudience" x-cloak role="status">
                <x-institution.icon name="warning" />
                <div class="ip-alert-text">{{ __('institution.competitions.audience_change_warning') }}</div>
            </div>
        </div>

        <div class="ip-form-actions ip-form-actions-sticky">
            <span class="ip-save-meta">{{ __('institution.competitions.last_saved_at', ['time' => $competition->updated_at->format('d.m.Y H:i')]) }}</span>
            <button type="submit" name="action" value="draft" class="ia-btn ia-btn-secondary">{{ __('institution.competitions.save_draft') }}</button>
            <button type="submit" name="action" value="next" class="ia-btn">{{ __('institution.competitions.next_step') }} →</button>
        </div>
    </form>
</x-institution.app-layout>
