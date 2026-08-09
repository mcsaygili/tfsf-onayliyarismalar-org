<x-eys.app-layout :title="__('eys.temsilci.edit_title')">
    <div class="ip-page-actions" style="justify-content: space-between;">
        <x-eys.breadcrumb :crumbs="[
            ['label' => __('eys.nav.dashboard'), 'url' => route('eys.dashboard')],
            ['label' => __('eys.module_names.Temsilci'), 'url' => route('eys.temsilci.dashboard')],
            ['label' => __('eys.temsilci.title'), 'url' => route('eys.temsilciler.index')],
            ['label' => __('eys.temsilci.edit_title')],
        ]" />
        <a href="{{ route('eys.temsilciler.index') }}" class="ia-btn ia-btn-secondary ip-btn-sm">
            <x-eys.icon name="back" />
            {{ __('eys.common.back') }}
        </a>
    </div>

    <div class="ip-card">
        <div class="ip-section-title">{{ __('eys.temsilci.edit_title') }}</div>

        <form method="POST" action="{{ route('eys.temsilciler.update', $temsilci) }}" novalidate autocomplete="off">
            @csrf
            @method('PATCH')

            <div class="ip-grid-2">
                <div class="ia-field">
                    <x-eys.label for="first_name" :value="__('eys.temsilci.first_name')" />
                    <x-eys.input id="first_name" type="text" name="first_name" :value="old('first_name', $temsilci->first_name)" autocomplete="off" />
                    <x-eys.input-error :messages="$errors->get('first_name')" />
                </div>
                <div class="ia-field">
                    <x-eys.label for="last_name" :value="__('eys.temsilci.last_name')" />
                    <x-eys.input id="last_name" type="text" name="last_name" :value="old('last_name', $temsilci->last_name)" autocomplete="off" />
                    <x-eys.input-error :messages="$errors->get('last_name')" />
                </div>
            </div>

            <div class="ia-field">
                <x-eys.label for="email" :value="__('eys.temsilci.column_email')" />
                <x-eys.input id="email" type="email" name="email" :value="old('email', $temsilci->email)" autocomplete="off" />
                <x-eys.input-error :messages="$errors->get('email')" />
            </div>

            <div class="ip-grid-2">
                <div class="ia-field">
                    <x-eys.label for="phone" :value="__('eys.temsilci.phone')" />
                    <x-eys.input id="phone" type="text" name="phone" :value="old('phone', $temsilci->phone)" autocomplete="off" />
                    <x-eys.input-error :messages="$errors->get('phone')" />
                </div>
                <div class="ia-field">
                    <x-eys.label for="education_level_id" :value="__('eys.temsilci.education_level')" />
                    <select id="education_level_id" name="education_level_id" class="ia-input">
                        <option value="">{{ __('eys.temsilci.education_level_none') }}</option>
                        @foreach ($educationLevels as $level)
                            <option value="{{ $level->id }}" @selected(old('education_level_id', $temsilci->education_level_id) === $level->id)>{{ $level->getTranslation()?->name }}</option>
                        @endforeach
                    </select>
                    <x-eys.input-error :messages="$errors->get('education_level_id')" />
                </div>
            </div>

            <div class="ia-field" style="margin-bottom: 0;" x-data="{ active: {{ old('status', (int) $temsilci->status) ? 'true' : 'false' }} }">
                <x-eys.label :value="__('eys.temsilci.status')" />
                <label class="ip-switch">
                    <input type="hidden" name="status" :value="active ? 1 : 0">
                    <input type="checkbox" class="ip-switch-checkbox" x-model="active">
                    <span class="ip-switch-track"><span class="ip-switch-thumb"></span></span>
                    <span class="ip-switch-label" x-text="active ? @js(__('eys.temsilci.status_active')) : @js(__('eys.temsilci.status_inactive'))"></span>
                </label>
                <x-eys.input-error :messages="$errors->get('status')" />
            </div>

            <div style="margin-top: 1.5rem;">
                <x-eys.button>{{ __('eys.common.update') }}</x-eys.button>
            </div>
        </form>
    </div>
</x-eys.app-layout>
