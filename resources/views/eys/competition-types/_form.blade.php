@props(['competitionType', 'locales'])

@php
    $initialLocale = old('_locale', $locales[0] ?? 'tr');
@endphp

<div x-data="{ loc: @js($initialLocale) }">
    <div class="ip-card ip-card-spaced">
        <div class="ip-section-title">{{ __('eys.competition_type.title') }}</div>

        <div class="ip-grid-2">
            <div class="ia-field">
                <x-eys.label for="code" :value="__('eys.competition_type.code')" />
                <x-eys.input id="code" type="text" name="code" :value="old('code', $competitionType->code)" autocomplete="off" />
                <div class="ip-field-hint">{{ __('eys.competition_type.code_hint') }}</div>
                <x-eys.input-error :messages="$errors->get('code')" />
            </div>
            <div class="ia-field">
                <x-eys.label for="sort_order" :value="__('eys.competition_type.sort_order')" />
                <x-eys.input id="sort_order" type="number" name="sort_order" min="0" :value="old('sort_order', $competitionType->sort_order)" autocomplete="off" />
                <div class="ip-field-hint">{{ __('eys.competition_type.sort_order_hint') }}</div>
                <x-eys.input-error :messages="$errors->get('sort_order')" />
            </div>
        </div>

        <div class="ia-field ip-field-last" x-data="{ active: {{ old('status', (int) $competitionType->status ?: 1) ? 'true' : 'false' }} }">
            <x-eys.label :value="__('eys.competition_type.status')" />
            <label class="ip-switch">
                <input type="hidden" name="status" :value="active ? 1 : 0">
                <input type="checkbox" class="ip-switch-checkbox" x-model="active">
                <span class="ip-switch-track"><span class="ip-switch-thumb"></span></span>
                <span class="ip-switch-label" x-text="active ? @js(__('eys.competition_type.status_active')) : @js(__('eys.competition_type.status_inactive'))"></span>
            </label>
            <x-eys.input-error :messages="$errors->get('status')" />
        </div>
    </div>

    <div class="ip-card">
        <div class="ip-section-title">{{ __('eys.competition_type.name') }} / {{ __('eys.competition_type.description') }}</div>

        <div class="ip-language-tabs" role="tablist" aria-label="{{ __('eys.competition_type.title') }}">
            @foreach ($locales as $locale)
                <button
                    type="button"
                    role="tab"
                    @click="loc = @js($locale)"
                    class="ip-language-tab"
                    :class="loc === @js($locale) ? 'is-active' : ''"
                    :aria-selected="(loc === @js($locale)).toString()"
                >{{ config("locales.supported.$locale") }}</button>
            @endforeach
        </div>

        @foreach ($locales as $locale)
            <div x-show="loc === @js($locale)" x-cloak class="ip-language-panel" role="tabpanel">
                <div class="ia-field">
                    <x-eys.label :for="$locale.'_name'" :value="__('eys.competition_type.name')" />
                    <x-eys.input :id="$locale.'_name'" type="text" :name="$locale.'[name]'" :value="old($locale.'.name', $competitionType->getTranslation($locale, false)?->name)" autocomplete="off" />
                    <x-eys.input-error :messages="$errors->get($locale.'.name')" />
                </div>

                <div class="ia-field ip-field-last">
                    <x-eys.label :for="$locale.'_description'" :value="__('eys.competition_type.description')" />
                    <textarea id="{{ $locale }}_description" name="{{ $locale }}[description]" class="ia-input" rows="5" maxlength="1000">{{ old($locale.'.description', $competitionType->getTranslation($locale, false)?->description) }}</textarea>
                    <div class="ip-field-hint">{{ __('eys.competition_type.description_hint') }}</div>
                    <x-eys.input-error :messages="$errors->get($locale.'.description')" />
                </div>
            </div>
        @endforeach
    </div>
</div>
