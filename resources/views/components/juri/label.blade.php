@props(['value'])

<label {{ $attributes->merge(['class' => 'ia-label']) }}>
    {{ $value ?? $slot }}
</label>
