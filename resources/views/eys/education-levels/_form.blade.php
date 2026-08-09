@props(['educationLevel', 'locales'])

@php
    $initialLocale = old('_locale', $locales[0] ?? 'tr');
@endphp

<div x-data="{ loc: @js($initialLocale) }">
    <div class="ip-card" style="margin-bottom: 1.5rem;">
        <div class="ip-section-title">{{ __('eys.education_level.title') }}</div>

        <div class="ip-grid-2">
            <div class="ia-field" style="margin-bottom: 0;">
                <x-eys.label for="sort_order" :value="__('eys.education_level.sort_order')" />
                <x-eys.input id="sort_order" type="number" name="sort_order" min="0" :value="old('sort_order', $educationLevel->sort_order)" autocomplete="off" />
                <div style="font-size: .78rem; color: var(--ia-muted-dim); margin-top: .35rem;">{{ __('eys.education_level.sort_order_hint') }}</div>
                <x-eys.input-error :messages="$errors->get('sort_order')" />
            </div>
            <div class="ia-field" style="margin-bottom: 0;" x-data="{ active: {{ old('status', (int) $educationLevel->status ?: 1) ? 'true' : 'false' }} }">
                <x-eys.label :value="__('eys.education_level.status')" />
                <label class="ip-switch">
                    <input type="hidden" name="status" :value="active ? 1 : 0">
                    <input type="checkbox" class="ip-switch-checkbox" x-model="active">
                    <span class="ip-switch-track"><span class="ip-switch-thumb"></span></span>
                    <span class="ip-switch-label" x-text="active ? @js(__('eys.education_level.status_active')) : @js(__('eys.education_level.status_inactive'))"></span>
                </label>
                <x-eys.input-error :messages="$errors->get('status')" />
            </div>
        </div>
    </div>

    <div class="ip-card">
        <div class="ip-section-title">{{ __('eys.education_level.name') }}</div>

        <div style="display: flex; gap: .5rem; margin-bottom: 1.25rem; border-bottom: 1px solid var(--ia-surface-border); padding-bottom: .75rem;">
            @foreach ($locales as $locale)
                <button type="button" @click="loc = @js($locale)" class="ia-btn ia-btn-secondary ip-btn-sm" :style="loc === @js($locale) ? 'border-color: var(--ia-copper-bright); color: var(--ia-cream);' : ''">{{ config("locales.supported.$locale") }}</button>
            @endforeach
        </div>

        @foreach ($locales as $locale)
            <div x-show="loc === @js($locale)" x-cloak>
                <div class="ia-field" style="margin-bottom: 0;">
                    <x-eys.label :for="$locale.'_name'" :value="__('eys.education_level.name')" />
                    <x-eys.input :id="$locale.'_name'" type="text" :name="$locale.'[name]'" :value="old($locale.'.name', $educationLevel->getTranslation($locale, false)?->name)" autocomplete="off" />
                    <x-eys.input-error :messages="$errors->get($locale.'.name')" />
                </div>
            </div>
        @endforeach
    </div>
</div>
