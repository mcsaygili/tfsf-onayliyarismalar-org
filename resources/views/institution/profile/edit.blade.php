<x-institution.app-layout :title="__('institution.nav.account')">
    <form method="POST" action="{{ route('institution.profile.update') }}" novalidate autocomplete="off">
        @csrf
        @method('PATCH')

        <div class="ip-card" style="margin-bottom: 1.5rem;">
            <div class="ip-section-title">{{ __('institution.profile.institution_section') }}</div>
            <div class="ip-section-hint">{{ __('institution.profile.institution_hint') }}</div>

            <div class="ia-field">
                <x-institution.label for="institution_name" :value="__('institution.profile.institution_name')" />
                <x-institution.input id="institution_name" type="text" name="institution_name" :value="old('institution_name', $staff->institution->name)" autocomplete="off" />
                <x-institution.input-error :messages="$errors->get('institution_name')" />
            </div>

            <div class="ip-grid-2">
                <div class="ia-field">
                    <x-institution.label for="institution_email" :value="__('institution.profile.institution_email')" />
                    <x-institution.input id="institution_email" type="email" name="institution_email" :value="old('institution_email', $staff->institution->email)" autocomplete="off" />
                    <x-institution.input-error :messages="$errors->get('institution_email')" />
                </div>
                <div class="ia-field">
                    <x-institution.label for="institution_phone" :value="__('institution.profile.institution_phone')" />
                    <x-institution.input id="institution_phone" type="text" name="institution_phone" :value="old('institution_phone', $staff->institution->phone)" autocomplete="off" />
                    <x-institution.input-error :messages="$errors->get('institution_phone')" />
                </div>
            </div>

            <div class="ia-field">
                <x-institution.label for="institution_website" :value="__('institution.profile.institution_website')" />
                <x-institution.input id="institution_website" type="text" name="institution_website" :value="old('institution_website', $staff->institution->website)" placeholder="https://" autocomplete="off" />
                <x-institution.input-error :messages="$errors->get('institution_website')" />
            </div>

            <div class="ia-field" style="margin-bottom: 0;">
                <x-institution.label for="institution_address" :value="__('institution.profile.institution_address')" />
                <textarea id="institution_address" name="institution_address" class="ia-input" rows="3" autocomplete="off">{{ old('institution_address', $staff->institution->address) }}</textarea>
                <x-institution.input-error :messages="$errors->get('institution_address')" />
            </div>
        </div>

        <div class="ip-card" style="margin-bottom: 1.5rem;">
            <div class="ip-section-title">{{ __('institution.profile.staff_section') }}</div>
            <div class="ip-section-hint">{{ __('institution.profile.staff_hint') }}</div>

            <div class="ip-grid-2">
                <div class="ia-field">
                    <x-institution.label for="first_name" :value="__('institution.profile.first_name')" />
                    <x-institution.input id="first_name" type="text" name="first_name" :value="old('first_name', $staff->first_name)" autocomplete="off" />
                    <x-institution.input-error :messages="$errors->get('first_name')" />
                </div>
                <div class="ia-field">
                    <x-institution.label for="last_name" :value="__('institution.profile.last_name')" />
                    <x-institution.input id="last_name" type="text" name="last_name" :value="old('last_name', $staff->last_name)" autocomplete="off" />
                    <x-institution.input-error :messages="$errors->get('last_name')" />
                </div>
            </div>

            <div class="ia-field" style="margin-bottom: 0;">
                <x-institution.label for="phone" :value="__('institution.profile.phone')" />
                <x-institution.input id="phone" type="text" name="phone" :value="old('phone', $staff->phone)" autocomplete="off" />
                <x-institution.input-error :messages="$errors->get('phone')" />
            </div>
        </div>

        <x-institution.button>{{ __('institution.profile.save') }}</x-institution.button>
    </form>
</x-institution.app-layout>
