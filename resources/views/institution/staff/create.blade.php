<x-institution.app-layout :title="__('institution.staff.create_title')">
    <div class="ip-page-actions">
        <a href="{{ route('institution.staff.index') }}" class="ia-btn ia-btn-secondary">
            <x-institution.icon name="back" />
            {{ __('institution.staff.back_to_list') }}
        </a>
    </div>

    <div class="ip-card">
        <div class="ip-section-title">{{ __('institution.staff.create_title') }}</div>
        <div class="ip-section-hint">{{ __('institution.staff.create_hint') }}</div>

        <form method="POST" action="{{ route('institution.staff.store') }}" novalidate autocomplete="off">
            @csrf

            <div class="ip-grid-2">
                <div class="ia-field">
                    <x-institution.label for="first_name" :value="__('institution.profile.first_name')" />
                    <x-institution.input id="first_name" type="text" name="first_name" :value="old('first_name')" autocomplete="off" />
                    <x-institution.input-error :messages="$errors->get('first_name')" />
                </div>
                <div class="ia-field">
                    <x-institution.label for="last_name" :value="__('institution.profile.last_name')" />
                    <x-institution.input id="last_name" type="text" name="last_name" :value="old('last_name')" autocomplete="off" />
                    <x-institution.input-error :messages="$errors->get('last_name')" />
                </div>
            </div>

            <div class="ia-field">
                <x-institution.label for="email" :value="__('institution.staff.column_email')" />
                <x-institution.input id="email" type="email" name="email" :value="old('email')" autocomplete="off" />
                <x-institution.input-error :messages="$errors->get('email')" />
            </div>

            <div class="ia-field">
                <x-institution.label for="phone" :value="__('institution.profile.phone')" />
                <x-institution.input id="phone" type="text" name="phone" :value="old('phone')" autocomplete="off" />
                <x-institution.input-error :messages="$errors->get('phone')" />
            </div>

            <div class="ip-grid-2">
                <div class="ia-field">
                    <x-institution.label for="password" :value="__('institution.staff.password')" />
                    <x-institution.input id="password" type="password" name="password" autocomplete="new-password" />
                    <x-institution.input-error :messages="$errors->get('password')" />
                </div>
                <div class="ia-field" style="margin-bottom: 0;">
                    <x-institution.label for="password_confirmation" :value="__('institution.staff.password_confirmation')" />
                    <x-institution.input id="password_confirmation" type="password" name="password_confirmation" autocomplete="new-password" />
                </div>
            </div>

            <div style="margin-top: 1.5rem;">
                <x-institution.button>{{ __('institution.staff.save_new') }}</x-institution.button>
            </div>
        </form>
    </div>
</x-institution.app-layout>
