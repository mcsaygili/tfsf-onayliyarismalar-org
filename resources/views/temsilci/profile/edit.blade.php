<x-temsilci.app-layout :title="__('temsilci.nav.profile')">
    <form method="POST" action="{{ route('temsilci.profile.update') }}" novalidate autocomplete="off">
        @csrf
        @method('PATCH')

        <div class="ip-card">
            <div class="ip-section-title">{{ __('temsilci.profile.section_title') }}</div>
            <div class="ip-section-hint">{{ __('temsilci.profile.section_hint') }}</div>

            <div class="ip-grid-2">
                <div class="ia-field">
                    <x-temsilci.label for="first_name" :value="__('temsilci.profile.first_name')" />
                    <x-temsilci.input id="first_name" type="text" name="first_name" :value="old('first_name', $temsilci->first_name)" autocomplete="off" />
                    <x-temsilci.input-error :messages="$errors->get('first_name')" />
                </div>
                <div class="ia-field">
                    <x-temsilci.label for="last_name" :value="__('temsilci.profile.last_name')" />
                    <x-temsilci.input id="last_name" type="text" name="last_name" :value="old('last_name', $temsilci->last_name)" autocomplete="off" />
                    <x-temsilci.input-error :messages="$errors->get('last_name')" />
                </div>
            </div>

            <div class="ia-field">
                <x-temsilci.label for="email" :value="__('temsilci.profile.email')" />
                <x-temsilci.input id="email" type="email" name="email" :value="old('email', $temsilci->email)" autocomplete="off" />
                <x-temsilci.input-error :messages="$errors->get('email')" />
            </div>

            <div class="ia-field" style="margin-bottom: 0;">
                <x-temsilci.label for="phone" :value="__('temsilci.profile.phone')" />
                <x-temsilci.input id="phone" type="text" name="phone" :value="old('phone', $temsilci->phone)" autocomplete="off" />
                <x-temsilci.input-error :messages="$errors->get('phone')" />
            </div>
        </div>

        <div style="margin-top: 1.5rem;">
            <x-temsilci.button>{{ __('temsilci.profile.save') }}</x-temsilci.button>
        </div>
    </form>
</x-temsilci.app-layout>
