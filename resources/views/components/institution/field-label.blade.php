@props([
    'for',
    'value',
    'description',
    'example',
    'group' => false,
])

@php
    $dialogId = 'field-help-'.$for;
    $titleId = $dialogId.'-title';
@endphp

<div class="ip-field-label-wrap" x-data>
    <div class="ip-field-label-row">
        @if ($group)
            <span class="ia-label">{{ $value }}</span>
        @else
            <label for="{{ $for }}" class="ia-label">{{ $value }}</label>
        @endif
        <button
            type="button"
            class="ip-field-help-button"
            x-ref="trigger"
            x-on:click="$refs.dialog.showModal(); $nextTick(() => $refs.close.focus())"
            aria-haspopup="dialog"
            aria-controls="{{ $dialogId }}"
            aria-label="{{ __('institution.field_help.open', ['field' => $value]) }}"
        >?</button>
    </div>

    <dialog
        id="{{ $dialogId }}"
        class="ip-field-help-dialog ip-native-dialog"
        x-ref="dialog"
        x-on:click="if ($event.target === $el) $el.close()"
        x-on:cancel.prevent="$refs.dialog.close()"
        x-on:close="$nextTick(() => $refs.trigger.focus())"
        aria-labelledby="{{ $titleId }}"
    >
        <div class="ip-field-help-panel">
            <div class="ip-field-help-header">
                <h2 id="{{ $titleId }}">{{ $value }}</h2>
                <button
                    type="button"
                    class="ip-field-help-close"
                    x-ref="close"
                    x-on:click="$refs.dialog.close()"
                    aria-label="{{ __('institution.field_help.close') }}"
                >&times;</button>
            </div>

            <div class="ip-field-help-body">
                <p class="ip-field-help-description">{{ $description }}</p>

                @if (filled($example))
                    <div class="ip-field-help-example">
                        <strong>{{ __('institution.field_help.example') }}</strong>
                        <span>{{ $example }}</span>
                    </div>
                @endif
            </div>

            <div class="ip-field-help-footer">
                <button type="button" class="ia-btn ia-btn-secondary ip-field-help-dismiss" x-on:click="$refs.dialog.close()">
                    {{ __('institution.field_help.done') }}
                </button>
            </div>
        </div>
    </dialog>
</div>
