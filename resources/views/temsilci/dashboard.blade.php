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

    <div class="ip-stats">
        <div class="ip-stat-card"><div class="ip-stat-icon"><x-temsilci.icon name="calendar" /></div><div><div class="ip-stat-value">{{ $competitionCount }}</div><div class="ip-stat-label">Atanmış yarışma</div></div></div>
        <div class="ip-stat-card"><div class="ip-stat-icon"><x-temsilci.icon name="staff" /></div><div><div class="ip-stat-value">{{ $pendingApprovalCount }}</div><div class="ip-stat-label">Bekleyen katılımcı onayı</div></div></div>
    </div>
    <div class="ip-card">
        <div class="ip-toolbar"><div><div class="ip-toolbar-title">Yaklaşan tarihler</div><div class="ip-toolbar-hint">Atandığınız yarışmaların değerlendirme bitişleri.</div></div><a class="ia-btn ia-btn-secondary ip-btn-sm" href="{{ route('temsilci.competitions.index') }}">Tümünü gör</a></div>
        <div class="ip-table-wrap"><table class="ip-table"><thead><tr><th>Yarışma</th><th>Değerlendirme bitişi</th><th></th></tr></thead><tbody>
            @forelse($upcomingDeadlines as $competition)<tr><td class="ip-cell-name">{{ $competition->name }}</td><td>{{ $competition->evaluation_ends_at->format('d.m.Y H:i') }}</td><td><a class="ip-row-icon-btn" href="{{ route('temsilci.competitions.show', $competition) }}"><x-temsilci.icon name="chevron-right" /></a></td></tr>
            @empty<tr><td colspan="3" class="ip-table-empty">Yaklaşan bir değerlendirme tarihi bulunmuyor.</td></tr>@endforelse
        </tbody></table></div>
    </div>
</x-temsilci.app-layout>
