<x-juri.app-layout :title="__('juri.nav.password')">
    <form method="POST" action="{{ route('juri.password.update') }}" novalidate autocomplete="off">
        @csrf
        @method('PUT')

        <div class="ip-card">
            <div class="ip-section-title">{{ __('juri.password.section_title') }}</div>
            <div class="ip-section-hint">{{ __('juri.password.section_hint') }}</div>

            <div class="ia-field">
                <x-juri.label for="current_password" :value="__('juri.password.current_password')" />
                <x-juri.input id="current_password" name="current_password" type="password" autocomplete="current-password" />
                <x-juri.input-error :messages="$errors->updatePassword->get('current_password')" />
            </div>

            <div class="ia-field">
                <x-juri.label for="password" :value="__('juri.password.new_password')" />
                <x-juri.input id="password" name="password" type="password" autocomplete="new-password" />
                <x-juri.input-error :messages="$errors->updatePassword->get('password')" />
            </div>

            <div class="ia-field" style="margin-bottom: 0;">
                <x-juri.label for="password_confirmation" :value="__('juri.password.confirm_password')" />
                <x-juri.input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" />
                <x-juri.input-error :messages="$errors->updatePassword->get('password_confirmation')" />
            </div>
        </div>

        <div style="margin-top: 1.5rem;">
            <x-juri.button>{{ __('juri.password.save') }}</x-juri.button>
        </div>
    </form>
</x-juri.app-layout>
