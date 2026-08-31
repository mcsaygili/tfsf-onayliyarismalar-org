<x-eys.app-layout :title="__('eys.uye.edit_title')">
    <div class="ip-page-actions" style="justify-content: space-between;">
        <x-eys.breadcrumb :crumbs="[
            ['label' => __('eys.nav.dashboard'), 'url' => route('eys.dashboard')],
            ['label' => __('eys.module_names.Uye'), 'url' => route('eys.uye.dashboard')],
            ['label' => __('eys.uye.title'), 'url' => route('eys.uyeler.index')],
            ['label' => __('eys.uye.edit_title')],
        ]" />
        <a href="{{ route('eys.uyeler.index') }}" class="ia-btn ia-btn-secondary ip-btn-sm">
            <x-eys.icon name="back" />
            {{ __('eys.common.back') }}
        </a>
    </div>

    <div class="ip-card ip-card-spaced">
        <div class="ip-section-title">{{ __('eys.uye.edit_title') }}</div>

        <form method="POST" action="{{ route('eys.uyeler.update', $member) }}" novalidate autocomplete="off">
            @csrf
            @method('PATCH')

            <div class="ip-grid-2">
                <div class="ia-field">
                    <x-eys.label for="first_name" :value="__('eys.uye.first_name')" />
                    <x-eys.input id="first_name" type="text" name="first_name" :value="old('first_name', $member->first_name)" autocomplete="off" />
                    <x-eys.input-error :messages="$errors->get('first_name')" />
                </div>
                <div class="ia-field">
                    <x-eys.label for="last_name" :value="__('eys.uye.last_name')" />
                    <x-eys.input id="last_name" type="text" name="last_name" :value="old('last_name', $member->last_name)" autocomplete="off" />
                    <x-eys.input-error :messages="$errors->get('last_name')" />
                </div>
            </div>

            <div class="ip-grid-2">
                <div class="ia-field">
                    <x-eys.label for="username" :value="__('eys.uye.username')" />
                    <x-eys.input id="username" type="text" name="username" :value="old('username', $member->username)" autocomplete="off" />
                    <x-eys.input-error :messages="$errors->get('username')" />
                </div>
                <div class="ia-field">
                    <x-eys.label for="email" :value="__('eys.uye.column_email')" />
                    <x-eys.input id="email" type="email" name="email" :value="old('email', $member->email)" autocomplete="off" />
                    <x-eys.input-error :messages="$errors->get('email')" />
                </div>
            </div>

            <div class="ip-grid-2">
                <div class="ia-field">
                    <x-eys.label for="phone_number" :value="__('eys.uye.phone_number')" />
                    <x-eys.input id="phone_number" type="text" name="phone_number" :value="old('phone_number', $member->phone_number)" autocomplete="off" />
                    <x-eys.input-error :messages="$errors->get('phone_number')" />
                </div>
                <div class="ia-field">
                    <x-eys.label for="education_level_id" :value="__('eys.uye.education_level')" />
                    <select id="education_level_id" name="education_level_id" class="ia-input">
                        <option value="">{{ __('eys.uye.education_level_none') }}</option>
                        @foreach ($educationLevels as $level)
                            <option value="{{ $level->id }}" @selected(old('education_level_id', $member->education_level_id) === $level->id)>{{ $level->getTranslation()?->name }}</option>
                        @endforeach
                    </select>
                    <x-eys.input-error :messages="$errors->get('education_level_id')" />
                </div>
            </div>

            <div class="ip-grid-2" style="margin-bottom: 0;">
                <div class="ia-field">
                    <x-eys.label for="uye_turu" :value="__('eys.uye.uye_turu')" />
                    <select id="uye_turu" name="uye_turu" class="ia-input">
                        @foreach ([0, 1, 2, 3] as $type)
                            <option value="{{ $type }}" @selected((int) old('uye_turu', $member->uye_turu) === $type)>{{ __('eys.uye.uye_turu_'.$type) }}</option>
                        @endforeach
                    </select>
                    <x-eys.input-error :messages="$errors->get('uye_turu')" />
                </div>
                <div class="ia-field" style="margin-bottom: 0;">
                    <x-eys.label for="status" :value="__('eys.uye.status')" />
                    <select id="status" name="status" class="ia-input">
                        <option value="1" @selected((int) old('status', $member->status) === 1)>{{ __('eys.uye.status_1') }}</option>
                        <option value="0" @selected((int) old('status', $member->status) === 0)>{{ __('eys.uye.status_0') }}</option>
                        <option value="90" @selected((int) old('status', $member->status) === 90)>{{ __('eys.uye.status_90') }}</option>
                    </select>
                    <x-eys.input-error :messages="$errors->get('status')" />
                </div>
            </div>

            <div style="margin-top: 1.5rem;">
                <x-eys.button>{{ __('eys.common.update') }}</x-eys.button>
            </div>
        </form>
    </div>

    <div class="ip-card ip-card-spaced">
        <div class="ip-toolbar">
            <div><div class="ip-toolbar-title">Üye geçmişi</div><div class="ip-toolbar-hint">Son yarışma katılımları ve eser hareketleri.</div></div>
            <span class="ip-badge {{ $member->activeRestrictions->isNotEmpty() ? 'is-inactive' : 'is-active' }}">{{ $member->activeRestrictions->isNotEmpty() ? 'Kısıtlı' : 'Katılıma uygun' }}</span>
        </div>
        <div class="ip-table-wrap">
            <table class="ip-table">
                <thead><tr><th>Yarışma</th><th>Durum</th><th>Kategori</th><th>Aktif fotoğraf</th><th>Tarih</th></tr></thead>
                <tbody>
                    @forelse($member->competitionEntries as $entry)
                        <tr><td class="ip-cell-name">{{ $entry->competition?->name ?: '—' }}</td><td>{{ $entry->status->value }}</td><td>{{ $entry->submissions->count() }}</td><td>{{ $entry->submissions->flatMap->photos->whereNull('withdrawn_at')->count() }}</td><td>{{ $entry->submitted_at?->format('d.m.Y H:i') ?: 'Taslak' }}</td></tr>
                    @empty<tr><td colspan="5" class="ip-table-empty">Üyenin henüz yarışma katılımı yok.</td></tr>@endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="ip-card ip-card-spaced">
        <div class="ip-section-title">Üye kısıtları</div>
        <div class="ip-section-hint">Katılım engelleri uygunluk motoruna doğrudan uygulanır ve geçmiş kayıtlar silinmez.</div>
        <div class="ip-table-wrap" style="margin-bottom:1.25rem;">
            <table class="ip-table"><thead><tr><th>Tür</th><th>Gerekçe</th><th>Süre</th><th>Durum</th><th></th></tr></thead><tbody>
                @forelse($member->restrictions->sortByDesc('created_at') as $restriction)
                    <tr><td>{{ $restriction->type === 'account' ? 'Hesap' : 'Yarışma katılımı' }}</td><td>{{ $restriction->reason }}</td><td>{{ $restriction->starts_at->format('d.m.Y H:i') }} — {{ $restriction->ends_at?->format('d.m.Y H:i') ?: 'Süresiz' }}</td><td><span class="ip-badge {{ $restriction->lifted_at ? 'is-draft' : ($restriction->ends_at?->isPast() ? 'is-draft' : 'is-inactive') }}">{{ $restriction->lifted_at ? 'Kaldırıldı' : ($restriction->ends_at?->isPast() ? 'Sona erdi' : 'Aktif') }}</span></td><td>
                        @if(!$restriction->lifted_at && (!$restriction->ends_at || $restriction->ends_at->isFuture()))
                            <form method="POST" action="{{ route('eys.uyeler.restrictions.lift', [$member, $restriction]) }}" style="display:flex;gap:.4rem;">@csrf @method('PATCH')<input class="ia-input" style="min-width:170px;padding:.45rem .6rem;" name="lift_reason" placeholder="Kaldırma gerekçesi" required><button class="ia-btn">Kaldır</button></form>
                        @endif
                    </td></tr>
                @empty<tr><td colspan="5" class="ip-table-empty">Kısıtlama kaydı bulunmuyor.</td></tr>@endforelse
            </tbody></table>
        </div>

        <form method="POST" action="{{ route('eys.uyeler.restrictions.store', $member) }}">@csrf
            <div class="ip-grid-2">
                <div class="ia-field"><x-eys.label for="restriction_type" value="Kısıt türü" /><select class="ia-input" id="restriction_type" name="type"><option value="competition">Yarışma katılımı</option><option value="account">Hesap erişimi</option></select></div>
                <div class="ia-field"><x-eys.label for="restriction_reason" value="Gerekçe" /><textarea class="ia-input" id="restriction_reason" name="reason" required>{{ old('reason') }}</textarea></div>
            </div>
            <div class="ip-grid-2">
                <div class="ia-field"><x-eys.label for="restriction_starts_at" value="Başlangıç" /><input class="ia-input" id="restriction_starts_at" type="datetime-local" name="starts_at" value="{{ old('starts_at', now()->format('Y-m-d\TH:i')) }}" required></div>
                <div class="ia-field"><x-eys.label for="restriction_ends_at" value="Bitiş (boşsa süresiz)" /><input class="ia-input" id="restriction_ends_at" type="datetime-local" name="ends_at" value="{{ old('ends_at') }}"></div>
            </div>
            <button class="ia-btn">Kısıtlama ekle</button>
        </form>
    </div>
</x-eys.app-layout>
