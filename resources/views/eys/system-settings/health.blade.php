<x-eys.app-layout title="Sistem sağlığı">
    <div class="ip-page-actions"><x-eys.breadcrumb :crumbs="[['label'=>__('eys.nav.dashboard'),'url'=>route('eys.dashboard')],['label'=>'Sistem sağlığı']]" /></div>
    <div class="ip-stats">
        @foreach($checks as $name => $check)
            <div class="ip-stat-card"><div class="ip-stat-icon"><x-eys.icon :name="$name === 'mail' ? 'mail' : ($name === 'storage' ? 'folder' : 'settings')" /></div><div><div class="ip-stat-label" style="margin:0 0 .3rem;text-transform:capitalize;">{{ $name }}</div><span class="ip-badge {{ $check['status'] === 'ok' ? 'is-active' : ($check['status'] === 'warning' ? 'is-pending' : 'is-inactive') }}">{{ $check['status'] }}</span><div class="ip-field-hint">{{ $check['detail'] }}</div></div></div>
        @endforeach
    </div>
    <div class="ip-card">
        <div class="ip-toolbar"><div><div class="ip-toolbar-title">Bildirim teslimatları</div><div class="ip-toolbar-hint">Kuyruk üzerinden çalışan son bildirim kanalı denemeleri.</div></div></div>
        <div class="ip-table-wrap"><table class="ip-table"><thead><tr><th>Zaman</th><th>Bildirim</th><th>Kanal</th><th>Durum</th><th>Alıcı</th></tr></thead><tbody>
            @forelse($deliveries as $delivery)<tr><td>{{ $delivery->attempted_at->format('d.m.Y H:i:s') }}</td><td class="ip-cell-name">{{ class_basename($delivery->notification_type) }}</td><td>{{ $delivery->channel }}</td><td><span class="ip-badge {{ $delivery->status === 'sent' ? 'is-active' : 'is-inactive' }}">{{ $delivery->status }}</span></td><td>{{ class_basename($delivery->notifiable_type) }} / {{ $delivery->notifiable_id }}</td></tr>
            @empty<tr><td colspan="5" class="ip-table-empty">Henüz teslimat kaydı bulunmuyor.</td></tr>@endforelse
        </tbody></table></div>
    </div>
</x-eys.app-layout>
