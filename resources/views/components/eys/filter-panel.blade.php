@props(['action', 'resetUrl' => null, 'total' => null])

<div class="ip-card ip-filter-panel">
    <div class="ip-filter-toggle-label">
        <x-eys.icon name="filter" />
        {{ __('eys.common.filters') }}
        @if (! is_null($total))
            <span class="ip-filter-total">— {{ __('eys.common.results_count', ['count' => $total]) }}</span>
        @endif
    </div>

    <form method="GET" action="{{ $action }}" class="ip-filter-grid">
        {{ $slot }}

        <div class="ip-filter-actions">
            <button type="submit" class="ia-btn">{{ __('eys.common.filter_apply') }}</button>
            @if ($resetUrl)
                <a href="{{ $resetUrl }}" class="ia-btn">{{ __('eys.common.filter_reset') }}</a>
            @endif
        </div>
    </form>
</div>
