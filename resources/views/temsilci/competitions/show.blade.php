<x-temsilci.app-layout :title="$competition->name">
    <div class="ip-page-actions"><a class="ia-btn ia-btn-secondary ip-btn-sm" href="{{ route('temsilci.competitions.index') }}">← Yarışmalara dön</a></div>
    <div class="ip-stats">
        <div class="ip-stat-card"><div><div class="ip-stat-value">{{ $competition->submitted_entry_count }}</div><div class="ip-stat-label">Gönderilen katılım</div></div></div>
        <div class="ip-stat-card"><div><div class="ip-stat-value">{{ $competition->pending_approval_count }}</div><div class="ip-stat-label">Bekleyen onay</div></div></div>
        <div class="ip-stat-card"><div><div class="ip-stat-value">{{ $competition->evaluationRounds->sum(fn($round) => $round->evaluationSubmissions->count()) }}</div><div class="ip-stat-label">Tamamlanan jüri görevi</div></div></div>
        <div class="ip-stat-card"><div><div class="ip-stat-value">{{ $competition->evaluationRounds->sum(fn($round) => $round->results->count()) }}</div><div class="ip-stat-label">Hesaplanan sonuç</div></div></div>
    </div>
    <div class="ip-card" style="margin-bottom:1.5rem;">
        <div class="ip-section-title">{{ $competition->name }}</div><div class="ip-section-hint">{{ $competition->institution?->name }} · {{ $competition->application_starts_at?->format('d.m.Y H:i') }} — {{ $competition->evaluation_ends_at?->format('d.m.Y H:i') }}</div>
        <div class="ip-grid-2"><div><strong>Kategoriler</strong><ul>@foreach($competition->categories as $category)<li>{{ $category->name }}</li>@endforeach</ul></div><div><strong>Operasyon durumu</strong><p>{{ __('eys.competitions.status.'.$competition->status->value) }}</p></div></div>
    </div>
    <div class="ip-card" style="margin-bottom:1.5rem;">
        <div class="ip-section-title">İzleme raporları</div><div class="ip-section-hint">Bu kayıtlar EYS yarışma inceleme ekranında da görünür.</div>
        <div class="ip-table-wrap"><table class="ip-table"><thead><tr><th>Tarih</th><th>Durum</th><th>Konu</th><th>Not</th></tr></thead><tbody>
            @forelse($competition->monitoringReports as $report)<tr><td>{{ $report->observed_at->format('d.m.Y H:i') }}</td><td>{{ $report->status }}</td><td class="ip-cell-name">{{ $report->subject }}</td><td>{{ $report->note }}</td></tr>@empty<tr><td colspan="4" class="ip-table-empty">Henüz izleme raporu yok.</td></tr>@endforelse
        </tbody></table></div>
    </div>
    <div class="ip-card">
        <div class="ip-section-title">Yeni izleme raporu</div><div class="ip-section-hint">Gözleminizi, riski veya tamamlanan operasyonu TFSF’ye iletin.</div>
        <form method="POST" action="{{ route('temsilci.competitions.reports.store', $competition) }}">@csrf
            <div class="ip-grid-2"><div class="ia-field"><label class="ia-label" for="report_status">Durum</label><select class="ia-input" id="report_status" name="status"><option value="observation">Gözlem</option><option value="risk">Risk</option><option value="completed">Tamamlandı</option></select></div><div class="ia-field"><label class="ia-label" for="observed_at">Gözlem zamanı</label><input class="ia-input" id="observed_at" type="datetime-local" name="observed_at" value="{{ now()->format('Y-m-d\TH:i') }}" required></div></div>
            <div class="ia-field"><label class="ia-label" for="subject">Konu</label><input class="ia-input" id="subject" name="subject" maxlength="255" required></div>
            <div class="ia-field"><label class="ia-label" for="note">Açıklama</label><textarea class="ia-input" id="note" name="note" maxlength="5000" required></textarea></div>
            <button class="ia-btn">Raporu gönder</button>
        </form>
    </div>
</x-temsilci.app-layout>
