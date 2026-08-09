<div class="ip-card">
    <div class="ip-section-title">{{ __('uye.profile.password_section_title') }}</div>
    <div class="ip-section-hint">{{ __('uye.profile.password_section_hint') }}</div>

    <form method="post" action="{{ route('password.update') }}" novalidate autocomplete="off">
        @csrf
        @method('put')

        <div class="ia-field">
            <x-uye.label for="update_password_current_password" :value="__('uye.profile.current_password')" />
            <x-uye.input id="update_password_current_password" name="current_password" type="password" autocomplete="current-password" />
            <x-uye.input-error :messages="$errors->updatePassword->get('current_password')" />
        </div>

        <div class="ia-field">
            <x-uye.label for="update_password_password" :value="__('uye.profile.new_password')" />
            <x-uye.input id="update_password_password" name="password" type="password" autocomplete="new-password" />
            <x-uye.input-error :messages="$errors->updatePassword->get('password')" />
        </div>

        <div class="ia-field" style="margin-bottom: 0;">
            <x-uye.label for="update_password_password_confirmation" :value="__('uye.profile.confirm_password')" />
            <x-uye.input id="update_password_password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" />
            <x-uye.input-error :messages="$errors->updatePassword->get('password_confirmation')" />
        </div>

        <div style="margin-top: 1.5rem; display: flex; align-items: center; gap: 1rem;">
            <x-uye.button>{{ __('uye.profile.save') }}</x-uye.button>

            @if (session('status') === 'password-updated')
                <span style="font-size: .85rem; color: var(--ia-muted);">{{ __('uye.profile.saved') }}</span>
            @endif
        </div>
    </form>
</div>
