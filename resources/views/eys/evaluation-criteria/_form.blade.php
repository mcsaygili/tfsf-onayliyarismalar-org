@props(['criterion', 'locales'])

<div x-data="{ loc: @js(old('_locale', $locales[0] ?? 'tr')), active: {{ old('status', (int) $criterion->status) ? 'true' : 'false' }} }">
    <div class="ip-card ip-card-spaced">
        <div class="ip-section-title">{{ __('eys.evaluation_criterion.general_information') }}</div>
        <div class="ip-section-hint">{{ __('eys.evaluation_criterion.general_hint') }}</div>
        <div class="ip-grid-2">
            <div class="ia-field">
                <x-eys.label for="code" :value="__('eys.evaluation_criterion.code')" />
                @if ($criterion->is_system)<input type="hidden" name="code" value="{{ $criterion->code }}">@endif
                <x-eys.input id="code" type="text" :name="$criterion->is_system ? null : 'code'" :value="old('code', $criterion->code)" :disabled="$criterion->is_system" autocomplete="off" />
                <div class="ip-field-hint">{{ __('eys.evaluation_criterion.code_hint') }}</div>
                <x-eys.input-error :messages="$errors->get('code')" />
            </div>
            <div class="ia-field">
                <x-eys.label for="sort_order" :value="__('eys.evaluation_criterion.sort_order')" />
                <x-eys.input id="sort_order" type="number" name="sort_order" min="0" max="65535" :value="old('sort_order', $criterion->sort_order)" />
                <x-eys.input-error :messages="$errors->get('sort_order')" />
            </div>
        </div>
        <div class="ip-grid-3" style="margin-top: 1rem;">
            <div class="ia-field"><x-eys.label for="default_min_score" :value="__('eys.evaluation_criterion.default_min_score')" /><x-eys.input id="default_min_score" type="number" name="default_min_score" min="0" max="9999" :value="old('default_min_score', $criterion->default_min_score)" /><x-eys.input-error :messages="$errors->get('default_min_score')" /></div>
            <div class="ia-field"><x-eys.label for="default_max_score" :value="__('eys.evaluation_criterion.default_max_score')" /><x-eys.input id="default_max_score" type="number" name="default_max_score" min="1" max="10000" :value="old('default_max_score', $criterion->default_max_score)" /><x-eys.input-error :messages="$errors->get('default_max_score')" /></div>
            <div class="ia-field"><x-eys.label for="default_weight" :value="__('eys.evaluation_criterion.default_weight')" /><x-eys.input id="default_weight" type="number" name="default_weight" min="0.01" max="999.99" step="0.01" :value="old('default_weight', $criterion->default_weight)" /><div class="ip-field-hint">{{ __('eys.evaluation_criterion.weight_hint') }}</div><x-eys.input-error :messages="$errors->get('default_weight')" /></div>
        </div>
        <div class="ia-field" style="margin-top: 1rem;">
            <x-eys.label :value="__('eys.evaluation_criterion.status')" />
            <label class="ip-switch"><input type="hidden" name="status" :value="active ? 1 : 0"><input type="checkbox" class="ip-switch-checkbox" x-model="active"><span class="ip-switch-track"><span class="ip-switch-thumb"></span></span><span class="ip-switch-label" x-text="active ? @js(__('eys.evaluation_criterion.status_active')) : @js(__('eys.evaluation_criterion.status_inactive'))"></span></label>
            <x-eys.input-error :messages="$errors->get('status')" />
        </div>
    </div>

    <div class="ip-card">
        <div class="ip-section-title">{{ __('eys.evaluation_criterion.translated_information') }}</div>
        <div class="ip-language-tabs" role="tablist" aria-label="{{ __('eys.evaluation_criterion.language_tabs') }}">
            @foreach ($locales as $locale)<button type="button" role="tab" @click="loc = @js($locale)" class="ip-language-tab" :class="loc === @js($locale) ? 'is-active' : ''" :aria-selected="(loc === @js($locale)).toString()">{{ config("locales.supported.$locale") }}</button>@endforeach
        </div>
        @foreach ($locales as $locale)
            <div x-show="loc === @js($locale)" x-cloak class="ip-language-panel" role="tabpanel" lang="{{ $locale }}">
                <div class="ia-field"><x-eys.label :for="$locale.'_name'" :value="__('eys.evaluation_criterion.name')" /><x-eys.input :id="$locale.'_name'" type="text" :name="$locale.'[name]'" :value="old($locale.'.name', $criterion->getTranslation($locale, false)?->name)" /><x-eys.input-error :messages="$errors->get($locale.'.name')" /></div>
                <div class="ia-field ip-field-last"><x-eys.label :for="$locale.'_description'" :value="__('eys.evaluation_criterion.description')" /><textarea id="{{ $locale }}_description" name="{{ $locale }}[description]" class="ia-input" rows="4" maxlength="1000">{{ old($locale.'.description', $criterion->getTranslation($locale, false)?->description) }}</textarea><x-eys.input-error :messages="$errors->get($locale.'.description')" /></div>
            </div>
        @endforeach
    </div>
</div>
