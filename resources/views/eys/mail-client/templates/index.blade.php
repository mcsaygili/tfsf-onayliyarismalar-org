<x-eys.app-layout :title="__('eys.mail_client.templates')">
    @include('eys.mail-client._nav', ['active' => 'templates'])

    <div class="ip-panel-stack">
        <div class="ip-card">
            <div class="ip-section-title">{{ __('eys.mail_client.templates') }}</div>
            <div class="ip-section-hint">{{ __('eys.mail_client.templates_hint') }}</div>

            <div class="ip-table-wrap" style="margin-top:1rem;">
                <table class="ip-table">
                    <thead><tr><th>{{ __('eys.mail_client.template_name') }}</th><th>{{ __('eys.mail_client.template_key') }}</th><th>TR</th><th>EN</th><th>{{ __('eys.mail_client.column_status') }}</th><th></th></tr></thead>
                    <tbody>
                    @foreach($templates as $template)
                        <tr>
                            <td class="ip-cell-name">{{ $template->name }}<small style="display:block;color:var(--ia-muted-dim);">{{ $template->description }}</small></td>
                            <td><code>{{ $template->key }}</code></td>
                            <td>{{ $template->translations->contains('locale', 'tr') ? '✓' : '—' }}</td>
                            <td>{{ $template->translations->contains('locale', 'en') ? '✓' : '—' }}</td>
                            <td><span class="ip-badge {{ $template->is_active ? 'is-active' : 'is-inactive' }}">{{ $template->is_active ? __('eys.mail_client.enabled_active') : __('eys.mail_client.enabled_inactive') }}</span></td>
                            <td><a class="ia-btn ia-btn-secondary ip-btn-sm" href="{{ route('eys.mail-client.templates.edit', $template) }}">{{ __('eys.common.edit') }}</a></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-eys.app-layout>
