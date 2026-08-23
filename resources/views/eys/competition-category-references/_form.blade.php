@props(['reference', 'locales', 'translation', 'hasAgeConstraints' => false])

@php
    $codeLabel = __("eys.$translation.code");
    $sortOrderLabel = __("eys.$translation.sort_order");
    $statusLabel = __("eys.$translation.status");
    $nameLabel = __("eys.$translation.name");
    $descriptionLabel = __("eys.$translation.description");
@endphp

<div x-data="{ loc: @js(old('_locale', $locales[0] ?? 'tr')) }">
    <div class="ip-card ip-card-spaced">
        <div class="ip-section-title">{{ __("eys.$translation.title") }}</div>
        <div class="ip-grid-2">
            <div class="ia-field">
                <x-eys.label for="code" :value="$codeLabel" />
                <x-eys.input id="code" name="code" :value="old('code', $reference->code)" />
                <div class="ip-field-hint">{{ __("eys.$translation.code_hint") }}</div>
                <x-eys.input-error :messages="$errors->get('code')" />
            </div>
            <div class="ia-field">
                <x-eys.label for="sort_order" :value="$sortOrderLabel" />
                <x-eys.input id="sort_order" type="number" name="sort_order" min="0" :value="old('sort_order', $reference->sort_order)" />
                <x-eys.input-error :messages="$errors->get('sort_order')" />
            </div>
        </div>
        @if ($hasAgeConstraints)
            <div class="ip-grid-2">
                <div class="ia-field">
                    <x-eys.label for="minimum_age" :value="__('eys.age_eligibility_rule.minimum_age')" />
                    <x-eys.input id="minimum_age" type="number" name="minimum_age" min="0" max="120" :value="old('minimum_age', $reference->minimum_age)" />
                    <x-eys.input-error :messages="$errors->get('minimum_age')" />
                </div>
                <div class="ia-field">
                    <x-eys.label for="maximum_age" :value="__('eys.age_eligibility_rule.maximum_age')" />
                    <x-eys.input id="maximum_age" type="number" name="maximum_age" min="0" max="120" :value="old('maximum_age', $reference->maximum_age)" />
                    <x-eys.input-error :messages="$errors->get('maximum_age')" />
                </div>
            </div>
            <div class="ip-grid-2">
                <label class="ip-switch"><input type="hidden" name="minimum_inclusive" value="0"><input type="checkbox" class="ip-switch-checkbox" name="minimum_inclusive" value="1" @checked(old('minimum_inclusive', $reference->minimum_inclusive ?? true))><span class="ip-switch-track"><span class="ip-switch-thumb"></span></span><span class="ip-switch-label">{{ __('eys.age_eligibility_rule.minimum_inclusive') }}</span></label>
                <label class="ip-switch"><input type="hidden" name="maximum_inclusive" value="0"><input type="checkbox" class="ip-switch-checkbox" name="maximum_inclusive" value="1" @checked(old('maximum_inclusive', $reference->maximum_inclusive ?? true))><span class="ip-switch-track"><span class="ip-switch-thumb"></span></span><span class="ip-switch-label">{{ __('eys.age_eligibility_rule.maximum_inclusive') }}</span></label>
            </div>
            <div class="ip-field-hint">{{ __('eys.age_eligibility_rule.age_hint') }}</div>
        @endif
        <div class="ia-field ip-field-last" x-data="{ active: {{ old('status', (int) $reference->status ?: 1) ? 'true' : 'false' }} }">
            <x-eys.label :value="$statusLabel" />
            <label class="ip-switch">
                <input type="hidden" name="status" :value="active ? 1 : 0">
                <input type="checkbox" class="ip-switch-checkbox" x-model="active">
                <span class="ip-switch-track"><span class="ip-switch-thumb"></span></span>
                <span class="ip-switch-label" x-text="active ? @js(__('eys.common.active')) : @js(__('eys.common.inactive'))"></span>
            </label>
        </div>
    </div>

    <div class="ip-card">
        <div class="ip-language-tabs" role="tablist">
            @foreach ($locales as $locale)
                <button type="button" class="ip-language-tab" :class="loc === @js($locale) ? 'is-active' : ''" @click="loc = @js($locale)">{{ config("locales.supported.$locale") }}</button>
            @endforeach
        </div>
        @foreach ($locales as $locale)
            <div x-show="loc === @js($locale)" x-cloak class="ip-language-panel">
                <div class="ia-field">
                    <x-eys.label :for="$locale.'_name'" :value="$nameLabel" />
                    <x-eys.input :id="$locale.'_name'" :name="$locale.'[name]'" :value="old($locale.'.name', $reference->getTranslation($locale, false)?->name)" />
                    <x-eys.input-error :messages="$errors->get($locale.'.name')" />
                </div>
                <div class="ia-field ip-field-last">
                    <x-eys.label :for="$locale.'_description'" :value="$descriptionLabel" />
                    <textarea id="{{ $locale }}_description" name="{{ $locale }}[description]" class="ia-input" rows="4" maxlength="1000">{{ old($locale.'.description', $reference->getTranslation($locale, false)?->description) }}</textarea>
                    <x-eys.input-error :messages="$errors->get($locale.'.description')" />
                </div>
            </div>
        @endforeach
    </div>
</div>
