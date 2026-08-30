@props(['award', 'locales'])

<div x-data="{ loc: @js(old('_locale', $locales[0] ?? 'tr')), active: {{ old('status', (int) $award->status) ? 'true' : 'false' }} }">
    <div class="ip-card ip-card-spaced">
        <div class="ip-section-title">{{ __('eys.award_reference.general_information') }}</div>
        <div class="ip-grid-2">
            <div class="ia-field">
                <x-eys.label for="code" :value="__('eys.award_reference.code')" />
                @if ($award->is_system)<input type="hidden" name="code" value="{{ $award->code }}">@endif
                <x-eys.input id="code" type="text" :name="$award->is_system ? null : 'code'" :value="old('code', $award->code)" :disabled="$award->is_system" autocomplete="off" />
                <div class="ip-field-hint">{{ __('eys.award_reference.code_hint') }}</div>
                <x-eys.input-error :messages="$errors->get('code')" />
            </div>
            <div class="ia-field">
                <x-eys.label for="sort_order" :value="__('eys.award_reference.sort_order')" />
                <x-eys.input id="sort_order" type="number" name="sort_order" min="0" max="65535" :value="old('sort_order', $award->sort_order)" />
                <x-eys.input-error :messages="$errors->get('sort_order')" />
            </div>
        </div>
        <div class="ip-grid-2" style="margin-top: 1rem;">
            <div class="ia-field">
                <x-eys.label for="kind" :value="__('eys.award_reference.kind')" />
                <select id="kind" name="kind" class="ia-input">
                    @foreach (['award', 'exhibition', 'purchase'] as $kind)<option value="{{ $kind }}" @selected(old('kind', $award->kind ?: 'award') === $kind)>{{ __('eys.award_reference.kinds.'.$kind) }}</option>@endforeach
                </select>
                <div class="ip-field-hint">{{ __('eys.award_reference.kind_hint') }}</div>
                <x-eys.input-error :messages="$errors->get('kind')" />
            </div>
            <div class="ia-field">
                <x-eys.label :value="__('eys.award_reference.status')" />
                <label class="ip-switch"><input type="hidden" name="status" :value="active ? 1 : 0"><input type="checkbox" class="ip-switch-checkbox" x-model="active"><span class="ip-switch-track"><span class="ip-switch-thumb"></span></span><span class="ip-switch-label" x-text="active ? @js(__('eys.award_reference.status_active')) : @js(__('eys.award_reference.status_inactive'))"></span></label>
                <x-eys.input-error :messages="$errors->get('status')" />
            </div>
        </div>
    </div>

    <div class="ip-card">
        <div class="ip-section-title">{{ __('eys.award_reference.translated_information') }}</div>
        <div class="ip-language-tabs" role="tablist">
            @foreach ($locales as $locale)<button type="button" role="tab" @click="loc = @js($locale)" class="ip-language-tab" :class="loc === @js($locale) ? 'is-active' : ''" :aria-selected="(loc === @js($locale)).toString()">{{ config("locales.supported.$locale") }}</button>@endforeach
        </div>
        @foreach ($locales as $locale)
            <div x-show="loc === @js($locale)" x-cloak class="ip-language-panel" role="tabpanel">
                <div class="ia-field"><x-eys.label :for="$locale.'_name'" :value="__('eys.award_reference.name')" /><x-eys.input :id="$locale.'_name'" type="text" :name="$locale.'[name]'" :value="old($locale.'.name', $award->getTranslation($locale, false)?->name)" /><x-eys.input-error :messages="$errors->get($locale.'.name')" /></div>
                <div class="ia-field ip-field-last"><x-eys.label :for="$locale.'_description'" :value="__('eys.award_reference.description')" /><textarea id="{{ $locale }}_description" name="{{ $locale }}[description]" class="ia-input" rows="4" maxlength="1000">{{ old($locale.'.description', $award->getTranslation($locale, false)?->description) }}</textarea><x-eys.input-error :messages="$errors->get($locale.'.description')" /></div>
            </div>
        @endforeach
    </div>
</div>
