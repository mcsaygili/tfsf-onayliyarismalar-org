<x-eys.app-layout :title="__('eys.institution_staff.edit_title')">
    <div class="ip-page-actions" style="justify-content: space-between;">
        <x-eys.breadcrumb :crumbs="[
            ['label' => __('eys.nav.dashboard'), 'url' => route('eys.dashboard')],
            ['label' => __('eys.module_names.Institution'), 'url' => route('eys.institution.dashboard')],
            ['label' => __('eys.institution.title'), 'url' => route('eys.institutions.index')],
            ['label' => $institution->name ?? '—', 'url' => route('eys.institution-staff.index', $institution)],
            ['label' => __('eys.institution_staff.edit_title')],
        ]" />
        <a href="{{ route('eys.institution-staff.index', $institution) }}" class="ia-btn ia-btn-secondary ip-btn-sm">
            <x-eys.icon name="back" />
            {{ __('eys.common.back') }}
        </a>
    </div>

    <div class="ip-card">
        <div class="ip-section-title">{{ __('eys.institution_staff.edit_title') }}</div>

        <form method="POST" action="{{ route('eys.institution-staff.update', [$institution, $staff]) }}" novalidate autocomplete="off">
            @csrf
            @method('PATCH')

            <div class="ip-grid-2">
                <div class="ia-field">
                    <x-eys.label for="first_name" :value="__('eys.institution_staff.first_name')" />
                    <x-eys.input id="first_name" type="text" name="first_name" :value="old('first_name', $staff->first_name)" autocomplete="off" />
                    <x-eys.input-error :messages="$errors->get('first_name')" />
                </div>
                <div class="ia-field">
                    <x-eys.label for="last_name" :value="__('eys.institution_staff.last_name')" />
                    <x-eys.input id="last_name" type="text" name="last_name" :value="old('last_name', $staff->last_name)" autocomplete="off" />
                    <x-eys.input-error :messages="$errors->get('last_name')" />
                </div>
            </div>

            <div class="ia-field">
                <x-eys.label for="email" :value="__('eys.institution_staff.column_email')" />
                <x-eys.input id="email" type="email" name="email" :value="old('email', $staff->email)" autocomplete="off" />
                <x-eys.input-error :messages="$errors->get('email')" />
            </div>

            <div class="ia-field">
                <x-eys.label for="phone" :value="__('eys.institution_staff.phone')" />
                <x-eys.input id="phone" type="text" name="phone" :value="old('phone', $staff->phone)" autocomplete="off" />
                <x-eys.input-error :messages="$errors->get('phone')" />
            </div>

            <div class="ia-field" style="margin-bottom: 0;" x-data="{ active: {{ old('status', (int) $staff->status) ? 'true' : 'false' }} }">
                <x-eys.label :value="__('eys.institution_staff.status')" />
                <label class="ip-switch">
                    <input type="hidden" name="status" :value="active ? 1 : 0">
                    <input type="checkbox" class="ip-switch-checkbox" x-model="active">
                    <span class="ip-switch-track"><span class="ip-switch-thumb"></span></span>
                    <span class="ip-switch-label" x-text="active ? @js(__('eys.institution_staff.status_active')) : @js(__('eys.institution_staff.status_inactive'))"></span>
                </label>
                <x-eys.input-error :messages="$errors->get('status')" />
            </div>

            <div style="margin-top: 1.5rem;">
                <x-eys.button>{{ __('eys.common.update') }}</x-eys.button>
            </div>
        </form>
    </div>
</x-eys.app-layout>
