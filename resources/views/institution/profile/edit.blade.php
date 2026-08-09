<x-institution.app-layout :title="__('institution.nav.institution_info')">
    <form method="POST" action="{{ route('institution.profile.update') }}" novalidate autocomplete="off">
        @csrf
        @method('PATCH')

        <div class="ip-card">
            <div class="ip-section-title">{{ __('institution.profile.institution_section') }}</div>
            <div class="ip-section-hint">{{ __('institution.profile.institution_hint') }}</div>

            <div class="ia-field">
                <x-institution.label for="institution_name" :value="__('institution.profile.institution_name')" />
                <x-institution.input id="institution_name" type="text" name="institution_name" :value="old('institution_name', $institution->name)" autocomplete="off" />
                <x-institution.input-error :messages="$errors->get('institution_name')" />
            </div>

            <div class="ip-grid-2">
                <div class="ia-field">
                    <x-institution.label for="institution_email" :value="__('institution.profile.institution_email')" />
                    <x-institution.input id="institution_email" type="email" name="institution_email" :value="old('institution_email', $institution->email)" autocomplete="off" />
                    <x-institution.input-error :messages="$errors->get('institution_email')" />
                </div>
                <div class="ia-field">
                    <x-institution.label for="institution_phone" :value="__('institution.profile.institution_phone')" />
                    <x-institution.input id="institution_phone" type="text" name="institution_phone" :value="old('institution_phone', $institution->phone)" autocomplete="off" />
                    <x-institution.input-error :messages="$errors->get('institution_phone')" />
                </div>
            </div>

            <div class="ia-field">
                <x-institution.label for="institution_website" :value="__('institution.profile.institution_website')" />
                <x-institution.input id="institution_website" type="text" name="institution_website" :value="old('institution_website', $institution->website)" placeholder="https://" autocomplete="off" />
                <x-institution.input-error :messages="$errors->get('institution_website')" />
            </div>

            <div class="ia-field" style="margin-bottom: 0;">
                <x-institution.label for="institution_address" :value="__('institution.profile.institution_address')" />
                <textarea id="institution_address" name="institution_address" class="ia-input" rows="3" autocomplete="off">{{ old('institution_address', $institution->address) }}</textarea>
                <x-institution.input-error :messages="$errors->get('institution_address')" />
            </div>
        </div>

        <div style="margin-top: 1.5rem;">
            <x-institution.button>{{ __('institution.profile.save') }}</x-institution.button>
        </div>
    </form>
</x-institution.app-layout>
