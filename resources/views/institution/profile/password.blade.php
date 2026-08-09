<x-institution.app-layout :title="__('institution.nav.password')">
    <form method="POST" action="{{ route('institution.password.update') }}" novalidate autocomplete="off">
        @csrf
        @method('PUT')

        <div class="ip-card">
            <div class="ip-section-title">{{ __('institution.password.section_title') }}</div>
            <div class="ip-section-hint">{{ __('institution.password.section_hint') }}</div>

            <div class="ia-field">
                <x-institution.label for="current_password" :value="__('institution.password.current_password')" />
                <x-institution.input id="current_password" name="current_password" type="password" autocomplete="current-password" />
                <x-institution.input-error :messages="$errors->updatePassword->get('current_password')" />
            </div>

            <div class="ia-field">
                <x-institution.label for="password" :value="__('institution.password.new_password')" />
                <x-institution.input id="password" name="password" type="password" autocomplete="new-password" />
                <x-institution.input-error :messages="$errors->updatePassword->get('password')" />
            </div>

            <div class="ia-field" style="margin-bottom: 0;">
                <x-institution.label for="password_confirmation" :value="__('institution.password.confirm_password')" />
                <x-institution.input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" />
                <x-institution.input-error :messages="$errors->updatePassword->get('password_confirmation')" />
            </div>
        </div>

        <div style="margin-top: 1.5rem;">
            <x-institution.button>{{ __('institution.password.save') }}</x-institution.button>
        </div>
    </form>
</x-institution.app-layout>
