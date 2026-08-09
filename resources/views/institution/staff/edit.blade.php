<x-institution.app-layout :title="__('institution.staff.edit_title')">
    <div class="ip-page-actions">
        <a href="{{ route('institution.staff.index') }}" class="ia-btn ia-btn-secondary">
            <x-institution.icon name="back" />
            {{ __('institution.staff.back_to_list') }}
        </a>
    </div>

    <div class="ip-card">
        <div class="ip-section-title">{{ __('institution.staff.edit_title') }}</div>
        <div class="ip-section-hint">{{ __('institution.staff.edit_hint') }}</div>

        <form method="POST" action="{{ route('institution.staff.update', $staff) }}" novalidate autocomplete="off">
            @csrf
            @method('PATCH')

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

            <div class="ia-field">
                <x-institution.label for="email" :value="__('institution.staff.column_email')" />
                <x-institution.input id="email" type="email" name="email" :value="old('email', $staff->email)" autocomplete="off" />
                <x-institution.input-error :messages="$errors->get('email')" />
            </div>

            <div class="ia-field">
                <x-institution.label for="phone" :value="__('institution.profile.phone')" />
                <x-institution.input id="phone" type="text" name="phone" :value="old('phone', $staff->phone)" autocomplete="off" />
                <x-institution.input-error :messages="$errors->get('phone')" />
            </div>

            <div class="ia-field" style="margin-bottom: 0;" x-data="{ active: {{ old('status', (int) $staff->status) ? 'true' : 'false' }} }">
                <x-institution.label :value="__('institution.staff.column_status')" />
                <label class="ip-switch">
                    <input type="hidden" name="status" :value="active ? 1 : 0">
                    <input type="checkbox" class="ip-switch-checkbox" x-model="active">
                    <span class="ip-switch-track"><span class="ip-switch-thumb"></span></span>
                    <span class="ip-switch-label" x-text="active ? @js(__('institution.staff.status_active')) : @js(__('institution.staff.status_inactive'))"></span>
                </label>
                <x-institution.input-error :messages="$errors->get('status')" />
            </div>

            <div style="margin-top: 1.5rem;">
                <x-institution.button>{{ __('institution.profile.save') }}</x-institution.button>
            </div>
        </form>
    </div>
</x-institution.app-layout>
