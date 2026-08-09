<x-juri.app-layout :title="__('juri.nav.profile')">
    <form method="POST" action="{{ route('juri.profile.update') }}" novalidate autocomplete="off">
        @csrf
        @method('PATCH')

        <div class="ip-card">
            <div class="ip-section-title">{{ __('juri.profile.section_title') }}</div>
            <div class="ip-section-hint">{{ __('juri.profile.section_hint') }}</div>

            <div class="ip-grid-2">
                <div class="ia-field">
                    <x-juri.label for="first_name" :value="__('juri.profile.first_name')" />
                    <x-juri.input id="first_name" type="text" name="first_name" :value="old('first_name', $juri->first_name)" autocomplete="off" />
                    <x-juri.input-error :messages="$errors->get('first_name')" />
                </div>
                <div class="ia-field">
                    <x-juri.label for="last_name" :value="__('juri.profile.last_name')" />
                    <x-juri.input id="last_name" type="text" name="last_name" :value="old('last_name', $juri->last_name)" autocomplete="off" />
                    <x-juri.input-error :messages="$errors->get('last_name')" />
                </div>
            </div>

            <div class="ia-field">
                <x-juri.label for="email" :value="__('juri.profile.email')" />
                <x-juri.input id="email" type="email" name="email" :value="old('email', $juri->email)" autocomplete="off" />
                <x-juri.input-error :messages="$errors->get('email')" />
            </div>

            <div class="ip-grid-2" style="margin-bottom: 0;">
                <div class="ia-field">
                    <x-juri.label for="phone" :value="__('juri.profile.phone')" />
                    <x-juri.input id="phone" type="text" name="phone" :value="old('phone', $juri->phone)" autocomplete="off" />
                    <x-juri.input-error :messages="$errors->get('phone')" />
                </div>
                <div class="ia-field" style="margin-bottom: 0;">
                    <x-juri.label for="tckimlikno" :value="__('juri.profile.tckimlikno')" />
                    <x-juri.input id="tckimlikno" type="text" name="tckimlikno" :value="old('tckimlikno', $juri->tckimlikno)" autocomplete="off" maxlength="11" />
                    <x-juri.input-error :messages="$errors->get('tckimlikno')" />
                </div>
            </div>
        </div>

        <div style="margin-top: 1.5rem;">
            <x-juri.button>{{ __('juri.profile.save') }}</x-juri.button>
        </div>
    </form>
</x-juri.app-layout>
