<x-institution.app-layout :title="__('institution.nav.dashboard')">
    @if (blank($institution->name) || blank($institution->email) || blank($institution->phone))
        <div class="ip-alert ip-alert-warning">
            <x-institution.icon name="warning" />
            <div>
                <div class="ip-alert-title">{{ __('institution.dashboard.incomplete_title') }}</div>
                <div class="ip-alert-text">
                    {{ __('institution.dashboard.incomplete_text') }}
                    <a href="{{ route('institution.profile.edit') }}">{{ __('institution.dashboard.incomplete_link') }}</a>
                </div>
            </div>
        </div>
    @endif

    <div class="ip-stats">
        <div class="ip-stat-card">
            <div class="ip-stat-icon"><x-institution.icon name="staff" /></div>
            <div>
                <div class="ip-stat-value">{{ $staffCount }}</div>
                <div class="ip-stat-label">{{ __('institution.dashboard.total_staff') }}</div>
            </div>
        </div>
    </div>

    <div class="ip-card">
        {{ __('Giriş yaptınız, hoş geldiniz :name.', ['name' => auth('institution')->user()->first_name ?? auth('institution')->user()->email]) }}
    </div>
</x-institution.app-layout>
