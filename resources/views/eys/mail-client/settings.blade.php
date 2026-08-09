<x-eys.app-layout :title="__('eys.mail_client.settings')">
    @include('eys.mail-client._nav', ['active' => 'settings'])

    <div class="ip-card">
        <div class="ip-section-title">{{ __('eys.mail_client.settings') }}</div>
        <div class="ip-section-hint">{{ __('eys.mail_client.settings_hint') }}</div>

        <form method="POST" action="{{ route('eys.mail-client.settings.update') }}" novalidate autocomplete="off">
            @csrf
            @method('PATCH')

            <div class="ip-grid-2">
                <div class="ia-field">
                    <x-eys.label for="daily_quota" :value="__('eys.mail_client.daily_quota')" />
                    <x-eys.input id="daily_quota" type="number" name="daily_quota" min="1" :value="old('daily_quota', $settings->daily_quota)" autocomplete="off" />
                    <div style="font-size: .78rem; color: var(--ia-muted-dim); margin-top: .35rem;">{{ __('eys.mail_client.daily_quota_hint') }}</div>
                    <x-eys.input-error :messages="$errors->get('daily_quota')" />
                </div>
                <div class="ia-field" style="margin-bottom: 0;">
                    <x-eys.label for="rate_per_second" :value="__('eys.mail_client.rate_per_second')" />
                    <x-eys.input id="rate_per_second" type="number" name="rate_per_second" min="1" :value="old('rate_per_second', $settings->rate_per_second)" autocomplete="off" />
                    <div style="font-size: .78rem; color: var(--ia-muted-dim); margin-top: .35rem;">{{ __('eys.mail_client.rate_per_second_hint') }}</div>
                    <x-eys.input-error :messages="$errors->get('rate_per_second')" />
                </div>
            </div>

            <div class="ia-field" style="margin-bottom: 0;" x-data="{ active: {{ old('enabled', (int) $settings->enabled) ? 'true' : 'false' }} }">
                <x-eys.label :value="__('eys.mail_client.enabled')" />
                <label class="ip-switch">
                    <input type="hidden" name="enabled" :value="active ? 1 : 0">
                    <input type="checkbox" class="ip-switch-checkbox" x-model="active">
                    <span class="ip-switch-track"><span class="ip-switch-thumb"></span></span>
                    <span class="ip-switch-label" x-text="active ? @js(__('eys.mail_client.enabled_active')) : @js(__('eys.mail_client.enabled_inactive'))"></span>
                </label>
                <x-eys.input-error :messages="$errors->get('enabled')" />
            </div>

            <div style="margin-top: 1.5rem;">
                <x-eys.button>{{ __('eys.mail_client.save') }}</x-eys.button>
            </div>
        </form>
    </div>
</x-eys.app-layout>
