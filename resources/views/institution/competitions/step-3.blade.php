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
          x-data="{ provider: @js(old('infrastructure_provider', $competition->infrastructure_provider?->value)) }">
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
                                x-model="provider"
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

        <section class="ip-card ip-card-spaced" x-show="provider === 'external'" x-cloak aria-labelledby="external-infrastructure-title" style="margin-top: 1.5rem;">
            <h2 id="external-infrastructure-title" class="ip-section-title">{{ __('institution.competitions.external_infrastructure_title') }}</h2>
            <div class="ip-section-hint">{{ __('institution.competitions.external_infrastructure_hint') }}</div>
            <div class="ip-grid-2">
                <div class="ia-field">
                    <x-institution.field-label for="external_provider_name" :value="__('institution.competitions.fields.external_provider_name')" :description="__('institution.competitions.field_help.external_provider_name.description')" :example="__('institution.competitions.field_help.external_provider_name.example')" />
                    <x-institution.input id="external_provider_name" name="external_provider_name" :value="old('external_provider_name', $competition->external_provider_name)" />
                    <x-institution.input-error :messages="$errors->get('external_provider_name')" />
                </div>
                <div class="ia-field">
                    <x-institution.field-label for="external_entry_url" :value="__('institution.competitions.fields.external_entry_url')" :description="__('institution.competitions.field_help.external_entry_url.description')" :example="__('institution.competitions.field_help.external_entry_url.example')" />
                    <x-institution.input id="external_entry_url" type="url" name="external_entry_url" :value="old('external_entry_url', $competition->external_entry_url)" placeholder="https://" />
                    <x-institution.input-error :messages="$errors->get('external_entry_url')" />
                </div>
            </div>
            <label class="ip-consent-row">
                <input type="checkbox" name="external_responsibility" value="1" @checked(old('external_responsibility', $competition->external_responsibility_accepted_at ? '1' : null))>
                <span><strong>{{ __('institution.competitions.external_responsibility_title') }}</strong><small>{{ __('institution.competitions.external_responsibility_text') }}</small></span>
            </label>
            <x-institution.input-error :messages="$errors->get('external_responsibility')" />
        </section>

        <div class="ip-form-actions ip-form-actions-sticky">
            <span class="ip-save-meta">{{ __('institution.competitions.last_saved_at', ['time' => $competition->updated_at->format('d.m.Y H:i')]) }}</span>
            <button type="submit" name="action" value="draft" class="ia-btn ia-btn-secondary">{{ __('institution.competitions.save_draft') }}</button>
            <button type="submit" name="action" value="next" class="ia-btn">{{ __('institution.competitions.next_step') }} →</button>
        </div>
    </form>
</x-institution.app-layout>
