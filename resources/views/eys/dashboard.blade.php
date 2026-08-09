<x-eys.app-layout :title="__('eys.nav.dashboard')">
    @if (blank($eysUser->first_name) || blank($eysUser->last_name))
        <div class="ip-alert ip-alert-warning">
            <x-eys.icon name="warning" />
            <div>
                <div class="ip-alert-title">{{ __('eys.dashboard.incomplete_title') }}</div>
                <div class="ip-alert-text">
                    {{ __('eys.dashboard.incomplete_text') }}
                    <a href="{{ route('eys.users.edit', $eysUser) }}">{{ __('eys.dashboard.incomplete_link') }}</a>
                </div>
            </div>
        </div>
    @endif

    <div class="ip-stats">
        <div class="ip-stat-card">
            <div class="ip-stat-icon"><x-eys.icon name="staff" /></div>
            <div>
                <div class="ip-stat-value">{{ $userCount }}</div>
                <div class="ip-stat-label">{{ __('eys.dashboard.total_users') }}</div>
            </div>
        </div>
    </div>

    <div class="ip-card">
        {{ __('Giriş yaptınız, hoş geldiniz :name.', ['name' => $eysUser->first_name ?? $eysUser->email]) }}
    </div>
</x-eys.app-layout>
