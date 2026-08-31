@props(['active'])

<div style="display: flex; gap: .5rem; margin-bottom: 1.5rem; flex-wrap: wrap;">
    <a href="{{ route('eys.mail-client.dashboard') }}" class="ia-btn ia-btn-secondary ip-btn-sm" style="{{ $active === 'dashboard' ? 'border-color: var(--ia-copper-bright); color: var(--ia-cream);' : '' }}">{{ __('eys.mail_client.dashboard_title') }}</a>
    <a href="{{ route('eys.mail-client.logs') }}" class="ia-btn ia-btn-secondary ip-btn-sm" style="{{ $active === 'logs' ? 'border-color: var(--ia-copper-bright); color: var(--ia-cream);' : '' }}">{{ __('eys.mail_client.logs') }}</a>
    <a href="{{ route('eys.mail-client.activity') }}" class="ia-btn ia-btn-secondary ip-btn-sm" style="{{ $active === 'activity' ? 'border-color: var(--ia-copper-bright); color: var(--ia-cream);' : '' }}">{{ __('eys.mail_client.activity') }}</a>
    @can('eys.mail_client.manage')
        <a href="{{ route('eys.mail-client.templates.index') }}" class="ia-btn ia-btn-secondary ip-btn-sm" style="{{ $active === 'templates' ? 'border-color: var(--ia-copper-bright); color: var(--ia-cream);' : '' }}">{{ __('eys.mail_client.templates') }}</a>
        <a href="{{ route('eys.mail-client.test') }}" class="ia-btn ia-btn-secondary ip-btn-sm" style="{{ $active === 'test' ? 'border-color: var(--ia-copper-bright); color: var(--ia-cream);' : '' }}">{{ __('eys.mail_client.test') }}</a>
        <a href="{{ route('eys.mail-client.settings') }}" class="ia-btn ia-btn-secondary ip-btn-sm" style="{{ $active === 'settings' ? 'border-color: var(--ia-copper-bright); color: var(--ia-cream);' : '' }}">{{ __('eys.mail_client.settings') }}</a>
    @endcan
</div>
