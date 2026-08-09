<x-temsilci.app-layout :title="__('temsilci.nav.password')">
    <form method="POST" action="{{ route('temsilci.password.update') }}" novalidate autocomplete="off">
        @csrf
        @method('PUT')

        <div class="ip-card">
            <div class="ip-section-title">{{ __('temsilci.password.section_title') }}</div>
            <div class="ip-section-hint">{{ __('temsilci.password.section_hint') }}</div>

            <div class="ia-field">
                <x-temsilci.label for="current_password" :value="__('temsilci.password.current_password')" />
                <x-temsilci.input id="current_password" name="current_password" type="password" autocomplete="current-password" />
                <x-temsilci.input-error :messages="$errors->updatePassword->get('current_password')" />
            </div>

            <div class="ia-field">
                <x-temsilci.label for="password" :value="__('temsilci.password.new_password')" />
                <x-temsilci.input id="password" name="password" type="password" autocomplete="new-password" />
                <x-temsilci.input-error :messages="$errors->updatePassword->get('password')" />
            </div>

            <div class="ia-field" style="margin-bottom: 0;">
                <x-temsilci.label for="password_confirmation" :value="__('temsilci.password.confirm_password')" />
                <x-temsilci.input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" />
                <x-temsilci.input-error :messages="$errors->updatePassword->get('password_confirmation')" />
            </div>
        </div>

        <div style="margin-top: 1.5rem;">
            <x-temsilci.button>{{ __('temsilci.password.save') }}</x-temsilci.button>
        </div>
    </form>
</x-temsilci.app-layout>
