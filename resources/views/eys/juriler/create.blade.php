<x-eys.app-layout :title="__('eys.juri.new')">
    <div class="ip-page-actions" style="justify-content: space-between;">
        <x-eys.breadcrumb :crumbs="[
            ['label' => __('eys.nav.dashboard'), 'url' => route('eys.dashboard')],
            ['label' => __('eys.module_names.Juri'), 'url' => route('eys.juri.dashboard')],
            ['label' => __('eys.juri.title'), 'url' => route('eys.juriler.index')],
            ['label' => __('eys.juri.new')],
        ]" />
        <a href="{{ route('eys.juriler.index') }}" class="ia-btn ia-btn-secondary ip-btn-sm">
            <x-eys.icon name="back" />
            {{ __('eys.common.back') }}
        </a>
    </div>

    <div class="ip-card">
        <div class="ip-section-title">{{ __('eys.juri.new') }}</div>

        <form method="POST" action="{{ route('eys.juriler.store') }}" novalidate autocomplete="off">
            @csrf

            <div class="ip-grid-2">
                <div class="ia-field">
                    <x-eys.label for="first_name" :value="__('eys.juri.first_name')" />
                    <x-eys.input id="first_name" type="text" name="first_name" :value="old('first_name')" autocomplete="off" />
                    <x-eys.input-error :messages="$errors->get('first_name')" />
                </div>
                <div class="ia-field">
                    <x-eys.label for="last_name" :value="__('eys.juri.last_name')" />
                    <x-eys.input id="last_name" type="text" name="last_name" :value="old('last_name')" autocomplete="off" />
                    <x-eys.input-error :messages="$errors->get('last_name')" />
                </div>
            </div>

            <div class="ip-grid-2">
                <div class="ia-field">
                    <x-eys.label for="email" :value="__('eys.juri.column_email')" />
                    <x-eys.input id="email" type="email" name="email" :value="old('email')" autocomplete="off" />
                    <x-eys.input-error :messages="$errors->get('email')" />
                </div>
                <div class="ia-field">
                    <x-eys.label for="tckimlikno" :value="__('eys.juri.tckimlikno')" />
                    <x-eys.input id="tckimlikno" type="text" name="tckimlikno" :value="old('tckimlikno')" maxlength="11" autocomplete="off" />
                    <x-eys.input-error :messages="$errors->get('tckimlikno')" />
                </div>
            </div>

            <div class="ip-grid-2">
                <div class="ia-field">
                    <x-eys.label for="phone" :value="__('eys.juri.phone')" />
                    <x-eys.input id="phone" type="text" name="phone" :value="old('phone')" autocomplete="off" />
                    <x-eys.input-error :messages="$errors->get('phone')" />
                </div>
                <div class="ia-field">
                    <x-eys.label for="education_level_id" :value="__('eys.juri.education_level')" />
                    <select id="education_level_id" name="education_level_id" class="ia-input">
                        <option value="">{{ __('eys.juri.education_level_none') }}</option>
                        @foreach ($educationLevels as $level)
                            <option value="{{ $level->id }}" @selected(old('education_level_id') === $level->id)>{{ $level->getTranslation()?->name }}</option>
                        @endforeach
                    </select>
                    <x-eys.input-error :messages="$errors->get('education_level_id')" />
                </div>
            </div>

            <div class="ip-grid-2">
                <div class="ia-field">
                    <x-eys.label for="password" :value="__('eys.juri.password')" />
                    <x-eys.input id="password" type="password" name="password" autocomplete="new-password" />
                    <x-eys.input-error :messages="$errors->get('password')" />
                </div>
                <div class="ia-field" style="margin-bottom: 0;">
                    <x-eys.label for="password_confirmation" :value="__('eys.juri.password_confirmation')" />
                    <x-eys.input id="password_confirmation" type="password" name="password_confirmation" autocomplete="new-password" />
                </div>
            </div>

            <div class="ia-field" style="margin-bottom: 0; margin-top: 1.35rem;" x-data="{ active: {{ old('status', 1) ? 'true' : 'false' }} }">
                <x-eys.label :value="__('eys.juri.status')" />
                <label class="ip-switch">
                    <input type="hidden" name="status" :value="active ? 1 : 0">
                    <input type="checkbox" class="ip-switch-checkbox" x-model="active">
                    <span class="ip-switch-track"><span class="ip-switch-thumb"></span></span>
                    <span class="ip-switch-label" x-text="active ? @js(__('eys.juri.status_active')) : @js(__('eys.juri.status_inactive'))"></span>
                </label>
                <x-eys.input-error :messages="$errors->get('status')" />
            </div>

            <div style="margin-top: 1.5rem;">
                <x-eys.button><x-eys.icon name="plus" />{{ __('eys.common.add') }}</x-eys.button>
            </div>
        </form>
    </div>
</x-eys.app-layout>
