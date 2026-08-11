<x-uye.app-layout :title="__('uye.nav.dashboard')">
    <div class="ip-stats">
        <div class="ip-stat-card">
            <div class="ip-stat-icon"><x-uye.icon name="camera" /></div>
            <div>
                <div class="ip-stat-value">{{ __('uye.dashboard.photo_count_value', ['count' => $photoCount, 'max' => $maxPhotos]) }}</div>
                <div class="ip-stat-label">{{ __('uye.dashboard.stat_photos') }}</div>
            </div>
        </div>
        <div class="ip-stat-card">
            <div class="ip-stat-icon"><x-uye.icon name="equipment" /></div>
            <div>
                <div class="ip-stat-value">{{ $equipmentCount }}</div>
                <div class="ip-stat-label">{{ __('uye.dashboard.stat_equipment') }}</div>
            </div>
        </div>
        <div class="ip-stat-card">
            <div class="ip-stat-icon"><x-uye.icon name="clock" /></div>
            <div>
                <div class="ip-stat-value" style="font-size: 1.1rem;">{{ $lastLoginAt?->format('d.m.Y H:i') ?? __('uye.dashboard.last_login_never') }}</div>
                <div class="ip-stat-label">{{ __('uye.dashboard.stat_last_login') }}</div>
            </div>
        </div>
        <div class="ip-stat-card">
            <div class="ip-stat-icon"><x-uye.icon name="calendar" /></div>
            <div>
                <div class="ip-stat-value" style="font-size: 1.1rem;">{{ $memberSince->format('d.m.Y') }}</div>
                <div class="ip-stat-label">{{ __('uye.dashboard.stat_member_since') }}</div>
            </div>
        </div>
    </div>

    <div class="ip-card">
        {{ __('uye.dashboard.welcome', ['name' => auth()->user()->first_name ?? auth()->user()->email]) }}
    </div>
</x-uye.app-layout>
