<x-uye.app-layout :title="__('uye.nav.account')">
    <div class="ip-panel-stack">
        <div class="ip-card">
            <div class="ip-section-title">Bildirim tercihleri</div>
            <div class="ip-section-hint">Süreç kayıtları hesap içinde saklanmaya devam eder; e-posta kanallarını buradan yönetebilirsiniz.</div>
            <form method="POST" action="{{ route('profile.preferences.update') }}">@csrf @method('PATCH')
                @php $preferences = auth()->user()->preferences ?? []; @endphp
                <label class="ip-switch" style="display:flex;margin-bottom:1rem;"><input class="ip-switch-checkbox" type="checkbox" name="results_email" value="1" @checked(data_get($preferences, 'results_email', true))><span class="ip-switch-track"><span class="ip-switch-thumb"></span></span><span class="ip-switch-label">Yarışma sonuçlarını e-posta ile bildir</span></label>
                <label class="ip-switch" style="display:flex;margin-bottom:1rem;"><input class="ip-switch-checkbox" type="checkbox" name="submission_database" value="1" @checked(data_get($preferences, 'submission_database', true))><span class="ip-switch-track"><span class="ip-switch-thumb"></span></span><span class="ip-switch-label">Katılım kararlarını bildirim merkezinde göster</span></label>
                <label class="ip-switch" style="display:flex;margin-bottom:1rem;"><input class="ip-switch-checkbox" type="checkbox" name="marketing_email" value="1" @checked(data_get($preferences, 'marketing_email', false))><span class="ip-switch-track"><span class="ip-switch-thumb"></span></span><span class="ip-switch-label">Yeni yarışma ve duyuru e-postaları</span></label>
                <button class="ia-btn">Tercihleri kaydet</button>
                @if(session('status') === 'preferences-updated')<span style="margin-left:.75rem;color:#a9d2ac;">Tercihler kaydedildi.</span>@endif
            </form>
        </div>
        @include('uye.profile.partials.delete-user-form')
    </div>
</x-uye.app-layout>
