@props(['item', 'sections', 'locales'])

@php
    $initialLocale = old('_locale', $locales[0] ?? 'tr');
@endphp

<div x-data="{ loc: @js($initialLocale) }">
    <div class="ip-card" style="margin-bottom: 1.5rem;">
        <div class="ip-section-title">{{ __('eys.regulation_item.title') }}</div>

        <div class="ia-field">
            <x-eys.label for="regulation_section_id" :value="__('eys.regulation_item.section')" />
            <select id="regulation_section_id" name="regulation_section_id" class="ia-input">
                <option value="">{{ __('eys.regulation_item.section_none') }}</option>
                @foreach ($sections as $option)
                    <option value="{{ $option->id }}" @selected(old('regulation_section_id', $item->regulation_section_id) === $option->id)>{{ $option->getTranslation()?->name }}</option>
                @endforeach
            </select>
            <x-eys.input-error :messages="$errors->get('regulation_section_id')" />
        </div>

        <div class="ip-grid-2">
            <div class="ia-field">
                <x-eys.label for="sort_order" :value="__('eys.regulation_item.sort_order')" />
                <x-eys.input id="sort_order" type="number" name="sort_order" min="0" :value="old('sort_order', $item->sort_order)" autocomplete="off" />
                <div style="font-size: .78rem; color: var(--ia-muted-dim); margin-top: .35rem;">{{ __('eys.regulation_item.sort_order_hint') }}</div>
                <x-eys.input-error :messages="$errors->get('sort_order')" />
            </div>
            <div class="ia-field">
                <x-eys.label for="code" :value="__('eys.regulation_item.code')" />
                <x-eys.input id="code" type="text" name="code" :value="old('code', $item->code)" autocomplete="off" />
                <div style="font-size: .78rem; color: var(--ia-muted-dim); margin-top: .35rem;">{{ __('eys.regulation_item.code_hint') }}</div>
                <x-eys.input-error :messages="$errors->get('code')" />
            </div>
        </div>

        <div class="ia-field" style="margin-bottom: 0;" x-data="{ active: {{ old('status', (int) $item->status ?: 1) ? 'true' : 'false' }} }">
            <x-eys.label :value="__('eys.regulation_item.status')" />
            <label class="ip-switch">
                <input type="hidden" name="status" :value="active ? 1 : 0">
                <input type="checkbox" class="ip-switch-checkbox" x-model="active">
                <span class="ip-switch-track"><span class="ip-switch-thumb"></span></span>
                <span class="ip-switch-label" x-text="active ? @js(__('eys.regulation_item.status_active')) : @js(__('eys.regulation_item.status_inactive'))"></span>
            </label>
            <x-eys.input-error :messages="$errors->get('status')" />
        </div>
    </div>

    <div class="ip-card">
        <div class="ip-section-title">{{ __('eys.regulation_item.content') }}</div>

        <div style="display: flex; gap: .5rem; margin-bottom: 1.25rem; border-bottom: 1px solid var(--ia-surface-border); padding-bottom: .75rem;">
            @foreach ($locales as $locale)
                <button type="button" @click="loc = @js($locale)" class="ia-btn ia-btn-secondary ip-btn-sm" :style="loc === @js($locale) ? 'border-color: var(--ia-copper-bright); color: var(--ia-cream);' : ''">{{ config("locales.supported.$locale") }}</button>
            @endforeach
        </div>

        @foreach ($locales as $locale)
            <div x-show="loc === @js($locale)" x-cloak>
                <div class="ia-field" style="margin-bottom: 0;">
                    <x-eys.label :for="$locale.'_content'" :value="__('eys.regulation_item.content')" />
                    <textarea id="{{ $locale }}_content" name="{{ $locale }}[content]" class="ia-input" rows="6">{{ old("$locale.content", $item->getTranslation($locale, false)?->content) }}</textarea>
                    <x-eys.input-error :messages="$errors->get($locale.'.content')" />
                </div>
            </div>
        @endforeach
    </div>
</div>
