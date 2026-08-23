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
            <div class="ip-section-title">{{ __('institution.competitions.steps.3.label') }}</div>
            <div class="ip-section-hint">{{ __('institution.competitions.steps.3.hint') }}</div>

            <fieldset class="ia-field ip-field-last">
                <legend class="ip-visually-hidden">{{ __('institution.competitions.fields.infrastructure_provider') }}</legend>

                <x-institution.field-label
                    for="infrastructure_provider"
                    :group="true"
                    :value="__('institution.competitions.fields.infrastructure_provider')"
                    :description="__('institution.competitions.field_help.infrastructure_provider.description')"
                    :example="__('institution.competitions.field_help.infrastructure_provider.example')"
                />

                <div class="ip-choice-options">
                    @foreach (['tfsf', 'external'] as $provider)
                        <label class="ip-choice-option" for="infrastructure_provider_{{ $provider }}">
                            <input
                                id="infrastructure_provider_{{ $provider }}"
                                type="radio"
                                name="infrastructure_provider"
                                value="{{ $provider }}"
                                @checked(old('infrastructure_provider', $competition->infrastructure_provider?->value) === $provider)
                                @if ($errors->has('infrastructure_provider')) aria-invalid="true" aria-describedby="infrastructure-provider-error" @endif
                            >
                            <span class="ip-choice-content">
                                <span class="ip-choice-heading">
                                    <strong>{{ __('institution.competitions.infrastructure_providers.'.$provider.'.title') }}</strong>
                                    <span class="ip-audience-language">{{ __('institution.competitions.infrastructure_providers.'.$provider.'.badge') }}</span>
                                </span>
                                <span class="ip-choice-description">{{ __('institution.competitions.infrastructure_providers.'.$provider.'.description') }}</span>
                                <span class="ip-choice-definition">
                                    <strong>{{ __('institution.competitions.infrastructure_providers.'.$provider.'.scope_title') }}</strong>
                                    <span>{{ __('institution.competitions.infrastructure_providers.'.$provider.'.definition') }}</span>

                                    @if ($provider === 'tfsf')
                                        <span class="ip-choice-services" role="list">
                                            @foreach (__('institution.competitions.infrastructure_providers.tfsf.services') as $service)
                                                <span class="ip-choice-service" role="listitem">{{ $service }}</span>
                                            @endforeach
                                        </span>
                                    @endif
                                </span>
                            </span>
                        </label>
                    @endforeach
                </div>

                <x-institution.input-error id="infrastructure-provider-error" :messages="$errors->get('infrastructure_provider')" />
            </fieldset>
        </div>

        <div class="ip-form-actions">
            <button type="submit" name="action" value="draft" class="ia-btn ia-btn-secondary">{{ __('institution.competitions.save_draft') }}</button>
            <button type="submit" name="action" value="next" class="ia-btn">{{ __('institution.competitions.next_step') }} →</button>
        </div>
    </form>
</x-institution.app-layout>
