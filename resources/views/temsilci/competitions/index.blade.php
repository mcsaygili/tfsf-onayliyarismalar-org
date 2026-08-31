<x-temsilci.app-layout title="Yarışma izleme">
    <div class="ip-card">
        <div class="ip-toolbar"><div><div class="ip-toolbar-title">Atanmış yarışmalar</div><div class="ip-toolbar-hint">TFSF tarafından sorumluluğunuza atanan yarışmalar.</div></div></div>
        <div class="ip-table-wrap"><table class="ip-table"><thead><tr><th>Yarışma</th><th>Kurum</th><th>Kategori</th><th>Katılım</th><th>İzleme kaydı</th><th>Değerlendirme</th><th></th></tr></thead><tbody>
            @forelse($competitions as $competition)
                <tr><td class="ip-cell-name">{{ $competition->name ?: 'İsimsiz yarışma' }}</td><td>{{ $competition->institution?->name ?: '—' }}</td><td>{{ $competition->categories_count }}</td><td>{{ $competition->entries_count }}</td><td>{{ $competition->monitoring_reports_count }}</td><td>{{ $competition->evaluation_ends_at?->format('d.m.Y H:i') ?: '—' }}</td><td><a class="ip-row-icon-btn" href="{{ route('temsilci.competitions.show', $competition) }}"><x-temsilci.icon name="chevron-right" /></a></td></tr>
            @empty<tr><td colspan="7" class="ip-table-empty">Henüz atanmış yarışma bulunmuyor.</td></tr>@endforelse
        </tbody></table></div>
        {{ $competitions->links() }}
    </div>
</x-temsilci.app-layout>
