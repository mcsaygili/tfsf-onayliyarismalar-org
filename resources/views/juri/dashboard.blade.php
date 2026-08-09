<x-juri.app-layout :title="__('juri.nav.dashboard')">
    @if (blank($juri->first_name) || blank($juri->last_name))
        <div class="ip-alert ip-alert-warning">
            <x-juri.icon name="warning" />
            <div>
                <div class="ip-alert-title">{{ __('juri.dashboard.incomplete_title') }}</div>
                <div class="ip-alert-text">
                    {{ __('juri.dashboard.incomplete_text') }}
                    <a href="{{ route('juri.profile.edit') }}">{{ __('juri.dashboard.incomplete_link') }}</a>
                </div>
            </div>
        </div>
    @endif

    <div class="ip-card">
        {{ __('Giriş yaptınız, hoş geldiniz :name.', ['name' => $juri->first_name ?? $juri->email]) }}
    </div>
</x-juri.app-layout>
