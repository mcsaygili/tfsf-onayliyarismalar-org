@props(['country', 'locales'])

@php
    $initialLocale = old('_locale', $locales[0] ?? 'tr');
@endphp

<div x-data="{ loc: @js($initialLocale) }">
    <div class="ip-card" style="margin-bottom: 1.5rem;">
        <div class="ip-section-title">{{ __('eys.country.title') }}</div>

        <div class="ip-grid-2">
            <div class="ia-field">
                <x-eys.label for="iso_alpha2" :value="__('eys.country.iso_alpha2')" />
                <x-eys.input id="iso_alpha2" type="text" name="iso_alpha2" :value="old('iso_alpha2', $country->iso_alpha2)" maxlength="2" style="text-transform: uppercase;" autocomplete="off" />
                <x-eys.input-error :messages="$errors->get('iso_alpha2')" />
            </div>
            <div class="ia-field" style="margin-bottom: 0;">
                <x-eys.label for="iso_alpha3" :value="__('eys.country.iso_alpha3')" />
                <x-eys.input id="iso_alpha3" type="text" name="iso_alpha3" :value="old('iso_alpha3', $country->iso_alpha3)" maxlength="3" style="text-transform: uppercase;" autocomplete="off" />
                <x-eys.input-error :messages="$errors->get('iso_alpha3')" />
            </div>
        </div>

        <div class="ia-field" style="margin-bottom: 0;" x-data="{ active: {{ old('status', (int) $country->status ?: 1) ? 'true' : 'false' }} }">
            <x-eys.label :value="__('eys.country.status')" />
            <label class="ip-switch">
                <input type="hidden" name="status" :value="active ? 1 : 0">
                <input type="checkbox" class="ip-switch-checkbox" x-model="active">
                <span class="ip-switch-track"><span class="ip-switch-thumb"></span></span>
                <span class="ip-switch-label" x-text="active ? @js(__('eys.country.status_active')) : @js(__('eys.country.status_inactive'))"></span>
            </label>
            <x-eys.input-error :messages="$errors->get('status')" />
        </div>
    </div>

    <div class="ip-card">
        <div class="ip-section-title">{{ __('eys.country.official_name') }}</div>

        <div style="display: flex; gap: .5rem; margin-bottom: 1.25rem; border-bottom: 1px solid var(--ia-surface-border); padding-bottom: .75rem;">
            @foreach ($locales as $locale)
                <button type="button" @click="loc = @js($locale)" class="ia-btn ia-btn-secondary ip-btn-sm" :style="loc === @js($locale) ? 'border-color: var(--ia-copper-bright); color: var(--ia-cream);' : ''">{{ config("locales.supported.$locale") }}</button>
            @endforeach
        </div>

        @foreach ($locales as $locale)
            <div x-show="loc === @js($locale)" x-cloak>
                <div class="ia-field">
                    <x-eys.label :for="$locale.'_official_name'" :value="__('eys.country.official_name')" />
                    <x-eys.input :id="$locale.'_official_name'" type="text" :name="$locale.'[official_name]'" :value="old($locale.'.official_name', $country->getTranslation($locale, false)?->official_name)" autocomplete="off" />
                    <x-eys.input-error :messages="$errors->get($locale.'.official_name')" />
                </div>
                <div class="ip-grid-2">
                    <div class="ia-field">
                        <x-eys.label :for="$locale.'_short_name'" :value="__('eys.country.short_name')" />
                        <x-eys.input :id="$locale.'_short_name'" type="text" :name="$locale.'[short_name]'" :value="old($locale.'.short_name', $country->getTranslation($locale, false)?->short_name)" autocomplete="off" />
                        <x-eys.input-error :messages="$errors->get($locale.'.short_name')" />
                    </div>
                    <div class="ia-field" style="margin-bottom: 0;">
                        <x-eys.label :for="$locale.'_nationality'" :value="__('eys.country.nationality')" />
                        <x-eys.input :id="$locale.'_nationality'" type="text" :name="$locale.'[nationality]'" :value="old($locale.'.nationality', $country->getTranslation($locale, false)?->nationality)" autocomplete="off" />
                        <x-eys.input-error :messages="$errors->get($locale.'.nationality')" />
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>
