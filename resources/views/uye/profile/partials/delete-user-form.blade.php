<div class="ip-card" x-data="{ open: false }">
    <div class="ip-section-title">{{ __('uye.profile.danger_section_title') }}</div>
    <div class="ip-section-hint" style="margin-bottom: 1rem;">{{ __('uye.profile.danger_section_hint') }}</div>

    <button type="button" class="ia-btn ia-btn-danger" @click="open = true">
        <x-uye.icon name="trash" />
        {{ __('uye.profile.delete_account') }}
    </button>

    <div class="ip-modal-overlay" x-show="open" x-cloak x-transition.opacity @keydown.escape.window="open = false">
        <div class="ip-modal" role="alertdialog" aria-modal="true" @click.outside="open = false">
            <div class="ip-section-title">{{ __('uye.profile.delete_confirm_title') }}</div>
            <p class="ip-modal-message">{{ __('uye.profile.delete_confirm_text') }}</p>

            <form method="post" action="{{ route('profile.destroy') }}">
                @csrf
                @method('delete')

                <div class="ia-field" style="margin-bottom: 0;">
                    <x-uye.label for="delete_password" value="{{ __('uye.profile.delete_password_placeholder') }}" class="sr-only" />
                    <x-uye.input id="delete_password" name="password" type="password" :placeholder="__('uye.profile.delete_password_placeholder')" />
                    <x-uye.input-error :messages="$errors->userDeletion->get('password')" />
                </div>

                <div class="ip-modal-actions">
                    <button type="button" class="ia-btn ia-btn-secondary" @click="open = false">{{ __('uye.profile.cancel') }}</button>
                    <button type="submit" class="ia-btn ia-btn-danger">{{ __('uye.profile.delete_account') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>
