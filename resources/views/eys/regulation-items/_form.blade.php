@props(['item', 'sections', 'locales', 'renderScopes', 'conditionFields', 'conditionOperators', 'templateTokens'])

@php
    $initialLocale = old('_locale', $locales[0] ?? 'tr');
    $storedConditions = old('conditions') ? json_decode(old('conditions'), true) : ($item->conditions ?? []);
    $initialConditions = data_get($storedConditions, 'all', []);
    if ($initialConditions === [] && $storedConditions !== []) {
        $fieldMap = ['audience' => 'competition.audience', 'infrastructure_provider' => 'competition.infrastructure_provider', 'competition_type' => 'competition.type_code'];
        $initialConditions = collect($storedConditions)->map(fn ($value, $field) => [
            'field' => $fieldMap[$field] ?? $field,
            'operator' => count((array) $value) > 1 ? 'in' : 'equals',
            'value' => count((array) $value) > 1 ? implode(',', (array) $value) : ((array) $value)[0],
        ])->values()->all();
    }
    $initialContentType = old('content_type', $item->content_type ?: 'template');
    $initialScope = old('render_scope', $item->render_scope ?: 'once');
@endphp

<div x-data="{
    loc: @js($initialLocale),
    contentType: @js($initialContentType),
    scope: @js($initialScope),
    conditions: @js($initialConditions),
    addCondition() { this.conditions.push({ field: 'competition.audience', operator: 'equals', value: '' }) }
}">
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
                <div class="ip-form-hint">{{ __('eys.regulation_item.sort_order_hint') }}</div>
                <x-eys.input-error :messages="$errors->get('sort_order')" />
            </div>
            <div class="ia-field">
                <x-eys.label for="code" :value="__('eys.regulation_item.code')" />
                @if ($item->is_system)<input type="hidden" name="code" value="{{ $item->code }}">@endif
                <x-eys.input id="code" type="text" :name="$item->is_system ? null : 'code'" :disabled="$item->is_system" :value="old('code', $item->code)" autocomplete="off" />
                <div class="ip-form-hint">{{ __('eys.regulation_item.code_hint') }}</div>
                <x-eys.input-error :messages="$errors->get('code')" />
            </div>
        </div>

        <div class="ip-grid-2">
            <div class="ia-field">
                <x-eys.label for="content_type" :value="__('eys.regulation_item.content_type')" />
                <select id="content_type" name="content_type" class="ia-input" x-model="contentType">
                    @foreach (['template', 'fixed', 'institution_input', 'source'] as $type)
                        <option value="{{ $type }}">{{ __('eys.regulation_item.content_types.'.$type) }}</option>
                    @endforeach
                </select>
                <x-eys.input-error :messages="$errors->get('content_type')" />
            </div>
            <div class="ia-field">
                <x-eys.label for="render_scope" :value="__('eys.regulation_item.render_scope')" />
                <select id="render_scope" name="render_scope" class="ia-input" x-model="scope">
                    @foreach ($renderScopes as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach
                </select>
                <x-eys.input-error :messages="$errors->get('render_scope')" />
            </div>
        </div>

        <div class="ia-field" x-show="contentType === 'source'" x-cloak>
            <x-eys.label for="source_key" :value="__('eys.regulation_item.source_key')" />
            <x-eys.input id="source_key" name="source_key" :value="old('source_key', $item->source_key)" />
            <x-eys.input-error :messages="$errors->get('source_key')" />
        </div>

        <div class="ia-field">
            <x-eys.label :value="__('eys.regulation_item.conditions')" />
            <p class="ip-form-hint" style="margin-bottom: .75rem;">{{ __('eys.regulation_item.conditions_hint') }}</p>
            <input type="hidden" name="conditions" :value="conditions.length ? JSON.stringify({ all: conditions }) : ''">
            <div class="ip-condition-list">
                <template x-for="(condition, index) in conditions" :key="index">
                    <div class="ip-condition-row">
                        <label>
                            <span>{{ __('eys.regulation_item.condition_field') }}</span>
                            <select class="ia-input" x-model="condition.field">
                                @foreach ($conditionFields as $value => $definition)<option value="{{ $value }}">{{ $definition['label'] }}</option>@endforeach
                            </select>
                        </label>
                        <label>
                            <span>{{ __('eys.regulation_item.condition_operator') }}</span>
                            <select class="ia-input" x-model="condition.operator">
                                @foreach ($conditionOperators as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach
                            </select>
                        </label>
                        <label x-show="!['exists', 'not_empty'].includes(condition.operator)">
                            <span>{{ __('eys.regulation_item.condition_value') }}</span>
                            <input type="text" class="ia-input" x-model="condition.value">
                        </label>
                        <button type="button" class="ia-btn ia-btn-secondary ip-btn-sm" @click="conditions.splice(index, 1)">{{ __('eys.regulation_item.condition_remove') }}</button>
                    </div>
                </template>
            </div>
            <button type="button" class="ia-btn ia-btn-secondary ip-btn-sm" @click="addCondition()" style="margin-top: .75rem;">+ {{ __('eys.regulation_item.condition_add') }}</button>
            <x-eys.input-error :messages="$errors->get('conditions')" />
        </div>

        <div class="ia-field" x-data="{ required: {{ old('is_required', (int) ($item->exists ? $item->is_required : true)) ? 'true' : 'false' }} }">
            <x-eys.label :value="__('eys.regulation_item.required')" />
            <label class="ip-switch">
                <input type="hidden" name="is_required" :value="required ? 1 : 0">
                <input type="checkbox" class="ip-switch-checkbox" x-model="required">
                <span class="ip-switch-track"><span class="ip-switch-thumb"></span></span>
                <span class="ip-switch-label">{{ __('eys.regulation_item.required_hint') }}</span>
            </label>
            <x-eys.input-error :messages="$errors->get('is_required')" />
        </div>

        <div class="ia-field" style="margin-bottom: 0;" x-data="{ active: {{ old('status', $item->exists ? (int) $item->status : 1) ? 'true' : 'false' }} }">
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

        <div class="ip-language-switch">
            @foreach ($locales as $locale)
                <button type="button" @click="loc = @js($locale)" class="ia-btn ia-btn-secondary ip-btn-sm" :style="loc === @js($locale) ? 'border-color: var(--ia-copper-bright); color: var(--ia-cream);' : ''">{{ config("locales.supported.$locale") }}</button>
            @endforeach
        </div>

        <div class="ip-template-guide" x-show="contentType === 'template'" x-cloak>
            <strong>{{ __('eys.regulation_item.template_tokens') }}</strong>
            <span>{{ __('eys.regulation_item.template_tokens_hint') }}</span>
            @foreach ($templateTokens as $tokenScope => $tokens)
                <div class="ip-token-list" x-show="scope === @js($tokenScope)">
                    @foreach ($tokens as $token => $label)<code title="{{ $label }}">{{ '{{ '.$token.' }}' }}</code>@endforeach
                </div>
            @endforeach
        </div>

        @foreach ($locales as $locale)
            <div x-show="loc === @js($locale)" x-cloak>
                <div class="ia-field" style="margin-bottom: 0;">
                    <x-eys.label :for="$locale.'_content'" :value="__('eys.regulation_item.content')" />
                    <textarea id="{{ $locale }}_content" name="{{ $locale }}[content]" class="ia-input" rows="7">{{ old("$locale.content", $item->getTranslation($locale, false)?->content) }}</textarea>
                    <x-eys.input-error :messages="$errors->get($locale.'.content')" />
                </div>
            </div>
        @endforeach
    </div>

    <style>
        .ip-form-hint { font-size: .78rem; color: var(--ia-muted-dim); margin-top: .35rem; }
        .ip-condition-list { display: grid; gap: .65rem; }
        .ip-condition-row { display: grid; grid-template-columns: 1.4fr 1fr 1fr auto; gap: .65rem; align-items: end; padding: .85rem; border: 1px solid var(--ia-surface-border); border-radius: 8px; background: rgba(0,0,0,.12); }
        .ip-condition-row label > span { display: block; margin-bottom: .35rem; color: var(--ia-muted-dim); font-size: .72rem; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; }
        .ip-language-switch { display: flex; gap: .5rem; margin-bottom: 1rem; border-bottom: 1px solid var(--ia-surface-border); padding-bottom: .75rem; }
        .ip-template-guide { padding: .9rem; margin-bottom: 1rem; border: 1px solid rgba(201,168,76,.25); border-radius: 8px; background: rgba(201,168,76,.05); }
        .ip-template-guide > strong, .ip-template-guide > span { display: block; }
        .ip-template-guide > strong { color: var(--ia-cream); font-size: .84rem; }
        .ip-template-guide > span { color: var(--ia-muted-dim); font-size: .76rem; margin-top: .2rem; }
        .ip-token-list { display: flex; flex-wrap: wrap; gap: .4rem; margin-top: .7rem; }
        .ip-token-list code { padding: .3rem .45rem; border-radius: 5px; background: rgba(255,255,255,.06); color: var(--ia-copper-bright); font-size: .72rem; }
        @media (max-width: 800px) { .ip-condition-row { grid-template-columns: 1fr; } }
    </style>
</div>
