@props(['status'])

@if ($status)
    <div {{ $attributes->merge(['class' => 'ia-status']) }}>
        {{ $status }}
    </div>
@endif
