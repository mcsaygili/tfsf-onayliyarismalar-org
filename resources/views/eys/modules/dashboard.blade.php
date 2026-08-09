<x-eys.app-layout :title="$moduleTitle">
    <div class="ip-stats">
        <div class="ip-stat-card">
            <div class="ip-stat-icon"><x-eys.icon :name="$icon" /></div>
            <div>
                <div class="ip-stat-value">{{ $totalCount }}</div>
                <div class="ip-stat-label">{{ $totalLabel }}</div>
            </div>
        </div>
    </div>
</x-eys.app-layout>
