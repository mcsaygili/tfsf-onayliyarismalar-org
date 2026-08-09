@props(['city', 'locales', 'countryOptions'])

@php
    $initialLocale = old('_locale', $locales[0] ?? 'tr');
@endphp

<div x-data="{ loc: @js($initialLocale) }">
    <div class="ip-card" style="margin-bottom: 1.5rem;">
        <div class="ip-section-title">{{ __('eys.city.title') }}</div>

        <div class="ia-field">
            <x-eys.label for="country_id" :value="__('eys.city.country')" />
            <select id="country_id" name="country_id" class="ia-input">
                <option value="" @selected(! old('country_id', $city->country_id))>—</option>
                @foreach ($countryOptions as $id => $label)
                    <option value="{{ $id }}" @selected(old('country_id', $city->country_id) === $id)>{{ $label }}</option>
                @endforeach
            </select>
            <x-eys.input-error :messages="$errors->get('country_id')" />
        </div>

        <div class="ia-field" style="margin-bottom: 0;" x-data="{ active: {{ old('status', (int) $city->status ?: 1) ? 'true' : 'false' }} }">
            <x-eys.label :value="__('eys.city.status')" />
            <label class="ip-switch">
                <input type="hidden" name="status" :value="active ? 1 : 0">
                <input type="checkbox" class="ip-switch-checkbox" x-model="active">
                <span class="ip-switch-track"><span class="ip-switch-thumb"></span></span>
                <span class="ip-switch-label" x-text="active ? @js(__('eys.city.status_active')) : @js(__('eys.city.status_inactive'))"></span>
            </label>
            <x-eys.input-error :messages="$errors->get('status')" />
        </div>
    </div>

    <div class="ip-card">
        <div class="ip-section-title">{{ __('eys.city.official_name') }}</div>

        <div style="display: flex; gap: .5rem; margin-bottom: 1.25rem; border-bottom: 1px solid var(--ia-surface-border); padding-bottom: .75rem;">
            @foreach ($locales as $locale)
                <button type="button" @click="loc = @js($locale)" class="ia-btn ia-btn-secondary ip-btn-sm" :style="loc === @js($locale) ? 'border-color: var(--ia-copper-bright); color: var(--ia-cream);' : ''">{{ config("locales.supported.$locale") }}</button>
            @endforeach
        </div>

        @foreach ($locales as $locale)
            <div x-show="loc === @js($locale)" x-cloak>
                <div class="ia-field" style="margin-bottom: 0;">
                    <x-eys.label :for="$locale.'_official_name'" :value="__('eys.city.official_name')" />
                    <x-eys.input :id="$locale.'_official_name'" type="text" :name="$locale.'[official_name]'" :value="old($locale.'.official_name', $city->getTranslation($locale, false)?->official_name)" autocomplete="off" />
                    <x-eys.input-error :messages="$errors->get($locale.'.official_name')" />
                </div>
            </div>
        @endforeach
    </div>
</div>
