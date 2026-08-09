@props(['name', 'label', 'value' => '', 'id' => null])

@php
    $id = $id ?? 'filter-'.$name;
@endphp

<div class="ip-filter-field">
    <label for="{{ $id }}">{{ $label }}</label>
    <div class="ip-filter-search">
        <x-eys.icon name="search" />
        <input type="text" id="{{ $id }}" name="{{ $name }}" value="{{ $value }}" class="ia-input" placeholder="{{ $label }}">
    </div>
</div>
