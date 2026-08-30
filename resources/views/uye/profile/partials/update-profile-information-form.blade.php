<div class="ip-card">
    <div class="ip-section-title">{{ __('uye.profile.section_title') }}</div>
    <div class="ip-section-hint">{{ __('uye.profile.section_hint') }}</div>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" novalidate autocomplete="off">
        @csrf
        @method('patch')

        <div class="ip-grid-2">
            <div class="ia-field">
                <x-uye.label for="first_name" :value="__('uye.profile.first_name')" />
                <x-uye.input id="first_name" type="text" name="first_name" :value="old('first_name', $user->first_name)" autocomplete="off" />
                <x-uye.input-error :messages="$errors->get('first_name')" />
            </div>
            <div class="ia-field">
                <x-uye.label for="last_name" :value="__('uye.profile.last_name')" />
                <x-uye.input id="last_name" type="text" name="last_name" :value="old('last_name', $user->last_name)" autocomplete="off" />
                <x-uye.input-error :messages="$errors->get('last_name')" />
            </div>
        </div>

        <div class="ia-field">
            <x-uye.label for="email" :value="__('uye.profile.email')" />
            <x-uye.input id="email" type="email" name="email" :value="old('email', $user->email)" autocomplete="off" />
            <x-uye.input-error :messages="$errors->get('email')" />

            @if (! $user->hasVerifiedEmail())
                <p style="margin-top: .6rem; font-size: .82rem; color: var(--ia-muted);">
                    {{ __('uye.profile.email_unverified') }}
                    <button form="send-verification" style="background: none; border: none; padding: 0; color: var(--ia-copper); text-decoration: underline; cursor: pointer; font-size: inherit; font-family: inherit;">{{ __('uye.profile.resend_verification') }}</button>
                </p>

                @if (session('status') === 'verification-link-sent')
                    <p style="margin-top: .4rem; font-size: .82rem; color: #a9d2ac;">{{ __('uye.profile.verification_resent') }}</p>
                @endif
            @endif
        </div>

        <div class="ia-field">
            <x-uye.label for="education_level_id" :value="__('uye.profile.education_level')" />
            <select id="education_level_id" name="education_level_id" class="ia-input">
                <option value="">{{ __('uye.profile.education_level_none') }}</option>
                @foreach ($educationLevels as $level)
                    <option value="{{ $level->id }}" @selected(old('education_level_id', $user->education_level_id) === $level->id)>{{ $level->getTranslation()?->name }}</option>
                @endforeach
            </select>
            <x-uye.input-error :messages="$errors->get('education_level_id')" />
        </div>

        <div class="ip-grid-2" style="margin-bottom: 0;">
            <div class="ia-field" style="margin-bottom: 0;">
                <x-uye.label for="gender" :value="__('uye.profile.gender')" />
                @php $gender = old('gender', $user->gender); @endphp
                <select id="gender" name="gender" class="ia-input">
                    <option value="">{{ __('uye.profile.gender_none') }}</option>
                    <option value="male" @selected($gender === 'male')>{{ __('uye.profile.gender_male') }}</option>
                    <option value="female" @selected($gender === 'female')>{{ __('uye.profile.gender_female') }}</option>
                    <option value="unspecified" @selected($gender === 'unspecified')>{{ __('uye.profile.gender_unspecified') }}</option>
                </select>
                <x-uye.input-error :messages="$errors->get('gender')" />
            </div>
            <div class="ia-field" style="margin-bottom: 0;">
                <x-uye.label for="date_of_birth_display" :value="__('uye.profile.date_of_birth')" />
                {{-- Alpine'ın @js() ile üretilen JS ifadesi, x-component etiketlerinin
                     attribute string'i içinde derlenmiyor (Blade @click/@keydown gibi
                     Alpine event syntax'ıyla çakışmasın diye burayı literal bırakıyor)
                     — bu yüzden burada düz <input> kullanılıyor. --}}
                <input
                    id="date_of_birth"
                    type="text"
                    name="date_of_birth"
                    class="ia-input"
                    value="{{ old('date_of_birth', $user->date_of_birth?->format('Y-m-d')) }}"
                    autocomplete="off"
                    x-data
                    x-init="
                        const fp = flatpickr($el, {
                            locale: @js(app()->getLocale() === 'tr' ? 'tr' : 'default'),
                            dateFormat: 'Y-m-d',
                            altInput: true,
                            altInputClass: 'ia-input',
                            altFormat: @js(app()->getLocale() === 'tr' ? 'd.m.Y' : 'F j, Y'),
                            maxDate: 'today',
                            allowInput: true,
                            disableMobile: true,
                        });
                        fp.altInput.id = 'date_of_birth_display';
                    "
                >
                <x-uye.input-error :messages="$errors->get('date_of_birth')" />
            </div>
        </div>

        <div class="ia-field" style="margin-top: 1rem;">
            <x-uye.label for="tckimlikno" :value="__('uye.profile.identity_number')" />
            <input id="tckimlikno" name="tckimlikno" class="ia-input" inputmode="numeric" maxlength="11" autocomplete="off" value="{{ old('tckimlikno', $user->tckimlikno) }}">
            <small class="ia-hint">{{ __('uye.profile.identity_hint') }}</small>
            <x-uye.input-error :messages="$errors->get('tckimlikno')" />
        </div>

        <div style="margin-top: 1.5rem; display: flex; align-items: center; gap: 1rem;">
            <x-uye.button>{{ __('uye.profile.save') }}</x-uye.button>

            @if (session('status') === 'profile-updated')
                <span style="font-size: .85rem; color: var(--ia-muted);">{{ __('uye.profile.saved') }}</span>
            @endif
        </div>
    </form>
</div>
