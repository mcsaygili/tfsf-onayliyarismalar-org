@props([
    'for',
    'value',
    'description',
    'example',
])

@php
    $dialogId = 'field-help-'.$for;
    $titleId = $dialogId.'-title';
@endphp

<div class="ip-field-label-wrap" x-data="{ helpOpen: false }" x-on:keydown.escape.window="if (helpOpen) { helpOpen = false; $nextTick(() => $refs.trigger.focus()) }">
    <div class="ip-field-label-row">
        <label for="{{ $for }}" class="ia-label">{{ $value }}</label>
        <button
            type="button"
            class="ip-field-help-button"
            x-ref="trigger"
            x-on:click="helpOpen = true; $nextTick(() => $refs.close.focus())"
            aria-haspopup="dialog"
            aria-controls="{{ $dialogId }}"
            x-bind:aria-expanded="helpOpen.toString()"
            aria-label="{{ __('institution.field_help.open', ['field' => $value]) }}"
        >?</button>
    </div>

    <div
        id="{{ $dialogId }}"
        class="ip-field-help-overlay"
        x-show="helpOpen"
        x-cloak
        x-transition.opacity
        x-on:click.self="helpOpen = false; $nextTick(() => $refs.trigger.focus())"
        role="presentation"
    >
        <div class="ip-field-help-dialog" role="dialog" aria-modal="true" aria-labelledby="{{ $titleId }}">
            <div class="ip-field-help-header">
                <h2 id="{{ $titleId }}">{{ $value }}</h2>
                <button
                    type="button"
                    class="ip-field-help-close"
                    x-ref="close"
                    x-on:click="helpOpen = false; $nextTick(() => $refs.trigger.focus())"
                    aria-label="{{ __('institution.field_help.close') }}"
                >&times;</button>
            </div>

            <p class="ip-field-help-description">{{ $description }}</p>

            <div class="ip-field-help-example">
                <strong>{{ __('institution.field_help.example') }}</strong>
                <span>{{ $example }}</span>
            </div>
        </div>
    </div>
</div>
