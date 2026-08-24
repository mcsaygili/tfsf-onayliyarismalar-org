@props(['process', 'locales'])

@php($initialLocale = old('_locale', $locales[0] ?? 'tr'))

<div x-data="{ loc: @js($initialLocale) }">
    <div class="ip-card ip-card-spaced">
        <div class="ip-section-title">{{ __('eys.participant_approval_process.title') }}</div>
        <div class="ip-grid-2">
            <div class="ia-field">
                <x-eys.label for="code" :value="__('eys.participant_approval_process.code')" />
                @if ($process->is_system)<input type="hidden" name="code" value="{{ $process->code }}">@endif
                <x-eys.input id="code" type="text" :name="$process->is_system ? null : 'code'" :value="old('code', $process->code)" autocomplete="off" :disabled="$process->is_system" />
                <div class="ip-field-hint">{{ __('eys.participant_approval_process.code_hint') }}</div>
                <x-eys.input-error :messages="$errors->get('code')" />
            </div>
            <div class="ia-field">
                <x-eys.label for="sort_order" :value="__('eys.participant_approval_process.sort_order')" />
                <x-eys.input id="sort_order" type="number" name="sort_order" min="0" :value="old('sort_order', $process->sort_order)" autocomplete="off" />
                <div class="ip-field-hint">{{ __('eys.participant_approval_process.sort_order_hint') }}</div>
                <x-eys.input-error :messages="$errors->get('sort_order')" />
            </div>
        </div>
        <div class="ia-field ip-field-last" x-data="{ active: {{ old('status', (int) $process->status ?: 1) ? 'true' : 'false' }} }">
            <x-eys.label :value="__('eys.participant_approval_process.status')" />
            <label class="ip-switch">
                <input type="hidden" name="status" :value="active ? 1 : 0">
                <input type="checkbox" class="ip-switch-checkbox" x-model="active">
                <span class="ip-switch-track"><span class="ip-switch-thumb"></span></span>
                <span class="ip-switch-label" x-text="active ? @js(__('eys.participant_approval_process.status_active')) : @js(__('eys.participant_approval_process.status_inactive'))"></span>
            </label>
            <x-eys.input-error :messages="$errors->get('status')" />
        </div>
    </div>

    <div class="ip-card">
        <div class="ip-section-title">{{ __('eys.participant_approval_process.name') }} / {{ __('eys.participant_approval_process.description') }}</div>
        <div class="ip-language-tabs" role="tablist" aria-label="{{ __('eys.participant_approval_process.title') }}">
            @foreach ($locales as $locale)
                <button type="button" role="tab" @click="loc = @js($locale)" class="ip-language-tab" :class="loc === @js($locale) ? 'is-active' : ''" :aria-selected="(loc === @js($locale)).toString()">{{ config("locales.supported.$locale") }}</button>
            @endforeach
        </div>
        @foreach ($locales as $locale)
            <div x-show="loc === @js($locale)" x-cloak class="ip-language-panel" role="tabpanel">
                <div class="ia-field">
                    <x-eys.label :for="$locale.'_name'" :value="__('eys.participant_approval_process.name')" />
                    <x-eys.input :id="$locale.'_name'" type="text" :name="$locale.'[name]'" :value="old($locale.'.name', $process->getTranslation($locale, false)?->name)" autocomplete="off" />
                    <x-eys.input-error :messages="$errors->get($locale.'.name')" />
                </div>
                <div class="ia-field ip-field-last">
                    <x-eys.label :for="$locale.'_description'" :value="__('eys.participant_approval_process.description')" />
                    <textarea id="{{ $locale }}_description" name="{{ $locale }}[description]" class="ia-input" rows="5" maxlength="1000">{{ old($locale.'.description', $process->getTranslation($locale, false)?->description) }}</textarea>
                    <div class="ip-field-hint">{{ __('eys.participant_approval_process.description_hint') }}</div>
                    <x-eys.input-error :messages="$errors->get($locale.'.description')" />
                </div>
            </div>
        @endforeach
    </div>
</div>
