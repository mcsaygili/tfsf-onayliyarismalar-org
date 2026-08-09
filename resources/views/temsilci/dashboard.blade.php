<x-temsilci.app-layout :title="__('temsilci.nav.dashboard')">
    @if (blank($temsilci->first_name) || blank($temsilci->last_name))
        <div class="ip-alert ip-alert-warning">
            <x-temsilci.icon name="warning" />
            <div>
                <div class="ip-alert-title">{{ __('temsilci.dashboard.incomplete_title') }}</div>
                <div class="ip-alert-text">
                    {{ __('temsilci.dashboard.incomplete_text') }}
                    <a href="{{ route('temsilci.profile.edit') }}">{{ __('temsilci.dashboard.incomplete_link') }}</a>
                </div>
            </div>
        </div>
    @endif

    <div class="ip-card">
        {{ __('Giriş yaptınız, hoş geldiniz :name.', ['name' => $temsilci->first_name ?? $temsilci->email]) }}
    </div>
</x-temsilci.app-layout>
