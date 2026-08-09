@props(['crumbs' => []])

@if (count($crumbs))
    <nav class="ip-breadcrumb" aria-label="breadcrumb">
        @foreach ($crumbs as $i => $crumb)
            @if ($i > 0)
                <span class="ip-breadcrumb-sep">/</span>
            @endif
            @if (! empty($crumb['url']) && $i < count($crumbs) - 1)
                <a href="{{ $crumb['url'] }}" class="ip-breadcrumb-item">{{ $crumb['label'] }}</a>
            @else
                <span class="ip-breadcrumb-item is-current">{{ $crumb['label'] }}</span>
            @endif
        @endforeach
    </nav>
@endif
