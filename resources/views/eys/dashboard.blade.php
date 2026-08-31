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
        @foreach([
            'submitted' => 'İnceleme bekleyen',
            'pending_approvals' => 'Katılımcı onayı',
            'pending_jurors' => 'Bekleyen jüri',
            'evaluation_open' => 'Değerlendirmede',
            'results_waiting' => 'Sonuç bekleyen',
        ] as $metric => $label)
            <div class="ip-stat-card">
                <div class="ip-stat-icon"><x-eys.icon :name="$metric === 'pending_jurors' ? 'juri' : ($metric === 'pending_approvals' ? 'list-check' : 'competitions')" /></div>
                <div><div class="ip-stat-value">{{ $metrics[$metric] }}</div><div class="ip-stat-label">{{ $label }}</div></div>
            </div>
        @endforeach
    </div>

    <div class="ip-card ip-card-spaced">
        <div class="ip-toolbar">
            <div><div class="ip-toolbar-title">Operasyon kuyruğu</div><div class="ip-toolbar-hint">Müdahale veya takip gerektiren yarışmalar tek görünümde.</div></div>
            <a class="ia-btn" href="{{ route('eys.competitions.index') }}">Tüm yarışmalar</a>
        </div>
        <div class="ip-table-wrap">
            <table class="ip-table">
                <thead><tr><th>Yarışma</th><th>Durum</th><th>Kritik tarihler</th><th>Bekleyenler</th><th>Jüri ilerlemesi</th><th></th></tr></thead>
                <tbody>
                    @forelse($attentionQueue as $competition)
                        <tr>
                            <td class="ip-cell-name"><strong>{{ $competition->name ?: 'İsimsiz taslak' }}</strong><small style="display:block;color:var(--ia-muted-dim);margin-top:.2rem;">{{ $competition->institution?->name ?: '—' }}</small></td>
                            <td><span class="ip-badge {{ $competition->status->badgeClass() }}">{{ __('eys.competitions.status.'.$competition->status->value) }}</span></td>
                            <td><small>Başvuru: {{ $competition->application_ends_at?->format('d.m.Y H:i') ?: '—' }}<br>Değerlendirme: {{ $competition->evaluation_ends_at?->format('d.m.Y H:i') ?: '—' }}</small></td>
                            <td><small>{{ $competition->pending_approval_count }} katılımcı onayı<br>{{ $competition->pending_juror_count }} jüri kaydı</small></td>
                            <td>{{ data_get($competition->jury_progress, 'completed', 0) }} / {{ data_get($competition->jury_progress, 'expected', 0) }}</td>
                            <td class="ip-table-actions"><a class="ip-row-icon-btn" href="{{ route('eys.competitions.show', $competition) }}" aria-label="İncele"><x-eys.icon name="chevron-right" /></a></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="ip-table-empty">Şu anda takip gerektiren bir yarışma bulunmuyor.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-eys.app-layout>
