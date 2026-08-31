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

    <div class="ip-card" style="margin-top:1.25rem;">
        <div class="ip-section-title">{{ __('eys.mail_client.domain_security') }}</div>
        <div class="ip-section-hint">{{ __('eys.mail_client.domain_security_hint') }}</div>
        <div class="ip-grid-2" style="margin-top:1rem;">
            <div><div class="ip-section-hint">{{ __('eys.mail_client.sending_domain') }}</div><strong>{{ $domainStatus['domain'] ?: '—' }}</strong></div>
            <div><div class="ip-section-hint">Resend</div><span class="ip-badge {{ $domainStatus['status'] === 'verified' ? 'is-active' : 'is-inactive' }}">{{ $domainStatus['status'] }}</span></div>
            <div><div class="ip-section-hint">SPF + DKIM</div><span class="ip-badge {{ $domainStatus['spf_dkim'] ? 'is-active' : 'is-inactive' }}">{{ $domainStatus['spf_dkim'] ? __('eys.mail_client.dns_verified') : __('eys.mail_client.dns_pending') }}</span></div>
            <div><div class="ip-section-hint">DMARC</div><span class="ip-badge {{ $domainStatus['dmarc'] ? 'is-active' : 'is-inactive' }}">{{ $domainStatus['dmarc'] ? __('eys.mail_client.dns_verified') : __('eys.mail_client.dns_pending') }}</span></div>
        </div>
        @unless($domainStatus['dmarc'])
            <div style="margin-top:1rem;padding:1rem;border:1px solid var(--ia-surface-border);border-radius:.75rem;color:var(--ia-muted);font-size:.82rem;overflow-wrap:anywhere;">
                <strong>TXT · _dmarc.{{ $domainStatus['domain'] }}</strong><br><code>{{ $recommendedDmarc }}</code>
            </div>
        @endunless
        @if($domainStatus['error'] ?? null)<div style="color:#e0857a;margin-top:.75rem;font-size:.82rem;">{{ $domainStatus['error'] }}</div>@endif
        <form method="POST" action="{{ route('eys.mail-client.domain.check') }}" style="display:flex;justify-content:flex-end;margin-top:1rem;">@csrf<button class="ia-btn ia-btn-secondary">{{ __('eys.mail_client.check_domain') }}</button></form>
    </div>
</x-eys.app-layout>
