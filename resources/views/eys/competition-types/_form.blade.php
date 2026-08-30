@props(['competitionType', 'locales', 'iconOptions'])

@php
    $initialLocale = old('_locale', $locales[0] ?? 'tr');
@endphp

<div x-data="{ loc: @js($initialLocale) }">
    <div class="ip-card ip-card-spaced">
        <div class="ip-section-title">{{ __('eys.competition_type.title') }}</div>

        <div class="ip-grid-2">
            <div class="ia-field">
                <x-eys.label for="code" :value="__('eys.competition_type.code')" />
                @if ($competitionType->is_system)<input type="hidden" name="code" value="{{ $competitionType->code }}">@endif
                <x-eys.input id="code" type="text" :name="$competitionType->is_system ? null : 'code'" :value="old('code', $competitionType->code)" autocomplete="off" :disabled="$competitionType->is_system" />
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

        <div class="ia-field" style="margin-top: 1rem;">
            <x-eys.label :value="__('eys.competition_type.icon')" />
            <div class="ip-icon-picker" role="radiogroup" aria-label="{{ __('eys.competition_type.icon') }}">
                @foreach ($iconOptions as $iconKey => $componentName)
                    <label class="ip-icon-choice">
                        <input type="radio" name="icon_key" value="{{ $iconKey }}" @checked(old('icon_key', $competitionType->icon_key ?: App\Support\CompetitionIcons\CompetitionIconRegistry::DEFAULT) === $iconKey)>
                        <span class="ip-icon-choice-preview"><x-public.icon :name="$iconKey" /></span>
                        <span>{{ __('eys.competition_type.icons.'.$iconKey) }}</span>
                    </label>
                @endforeach
            </div>
            <div class="ip-field-hint">{{ __('eys.competition_type.icon_hint') }}</div>
            <x-eys.input-error :messages="$errors->get('icon_key')" />
        </div>

        <div class="ip-grid-2" style="margin-top: 1rem;">
            <label class="ip-switch"><input type="hidden" name="requires_location" value="0"><input type="checkbox" class="ip-switch-checkbox" name="requires_location" value="1" @checked(old('requires_location', $competitionType->requires_location))><span class="ip-switch-track"><span class="ip-switch-thumb"></span></span><span class="ip-switch-label">{{ __('eys.competition_type.requires_location') }}</span></label>
            <label class="ip-switch"><input type="hidden" name="requires_approval_process" value="0"><input type="checkbox" class="ip-switch-checkbox" name="requires_approval_process" value="1" @checked(old('requires_approval_process', $competitionType->requires_approval_process))><span class="ip-switch-track"><span class="ip-switch-thumb"></span></span><span class="ip-switch-label">{{ __('eys.competition_type.requires_approval_process') }}</span></label>
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
