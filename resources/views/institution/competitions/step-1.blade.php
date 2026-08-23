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

    <form method="POST" action="{{ route('institution.competitions.step.update', [$competition, $step]) }}" novalidate autocomplete="off">
        @csrf
        @method('PUT')

        <div class="ip-card">
            <div class="ip-section-title">{{ __('institution.competitions.steps.1.label') }}</div>
            <div class="ip-section-hint">{{ __('institution.competitions.steps.1.hint') }}</div>

            <fieldset class="ia-field" style="margin-bottom: 0;">
                <legend class="ip-visually-hidden">{{ __('institution.competitions.fields.audience') }}</legend>

                <x-institution.field-label
                    for="audience"
                    :group="true"
                    :value="__('institution.competitions.fields.audience')"
                    :description="__('institution.competitions.field_help.audience.description')"
                    :example="__('institution.competitions.field_help.audience.example')"
                />

                <div class="ip-audience-options">
                    @foreach (['national', 'international'] as $audience)
                        <label class="ip-audience-option" for="audience_{{ $audience }}">
                            <input
                                id="audience_{{ $audience }}"
                                type="radio"
                                name="audience"
                                value="{{ $audience }}"
                                @checked(old('audience', $competition->audience?->value) === $audience)
                            >
                            <span class="ip-audience-content">
                                <span class="ip-audience-heading">
                                    <strong>{{ __('institution.competitions.audiences.'.$audience.'.title') }}</strong>
                                    <span class="ip-audience-language">{{ __('institution.competitions.audiences.'.$audience.'.language') }}</span>
                                </span>
                                <span class="ip-audience-description">{{ __('institution.competitions.audiences.'.$audience.'.description') }}</span>
                                <span class="ip-audience-definition">
                                    <strong>{{ __('institution.competitions.audience_definition') }}</strong>
                                    {{ __('institution.competitions.audiences.'.$audience.'.definition') }}
                                </span>
                            </span>
                        </label>
                    @endforeach
                </div>

                <x-institution.input-error :messages="$errors->get('audience')" />
            </fieldset>
        </div>

        <div style="margin-top: 1.5rem; display: flex; gap: .75rem;">
            <button type="submit" name="action" value="draft" class="ia-btn ia-btn-secondary">{{ __('institution.competitions.save_draft') }}</button>
            <button type="submit" name="action" value="next" class="ia-btn">{{ __('institution.competitions.next_step') }} →</button>
        </div>
    </form>
</x-institution.app-layout>
