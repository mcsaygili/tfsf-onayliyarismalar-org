<x-eys.app-layout :title="__('eys.uye.new')">
    <div class="ip-page-actions" style="justify-content: space-between;">
        <x-eys.breadcrumb :crumbs="[
            ['label' => __('eys.nav.dashboard'), 'url' => route('eys.dashboard')],
            ['label' => __('eys.module_names.Uye'), 'url' => route('eys.uye.dashboard')],
            ['label' => __('eys.uye.title'), 'url' => route('eys.uyeler.index')],
            ['label' => __('eys.uye.new')],
        ]" />
        <a href="{{ route('eys.uyeler.index') }}" class="ia-btn ia-btn-secondary ip-btn-sm">
            <x-eys.icon name="back" />
            {{ __('eys.common.back') }}
        </a>
    </div>

    <div class="ip-card">
        <div class="ip-section-title">{{ __('eys.uye.new') }}</div>

        <form method="POST" action="{{ route('eys.uyeler.store') }}" novalidate autocomplete="off">
            @csrf

            <div class="ip-grid-2">
                <div class="ia-field">
                    <x-eys.label for="first_name" :value="__('eys.uye.first_name')" />
                    <x-eys.input id="first_name" type="text" name="first_name" :value="old('first_name')" autocomplete="off" />
                    <x-eys.input-error :messages="$errors->get('first_name')" />
                </div>
                <div class="ia-field">
                    <x-eys.label for="last_name" :value="__('eys.uye.last_name')" />
                    <x-eys.input id="last_name" type="text" name="last_name" :value="old('last_name')" autocomplete="off" />
                    <x-eys.input-error :messages="$errors->get('last_name')" />
                </div>
            </div>

            <div class="ip-grid-2">
                <div class="ia-field">
                    <x-eys.label for="username" :value="__('eys.uye.username')" />
                    <x-eys.input id="username" type="text" name="username" :value="old('username')" autocomplete="off" />
                    <x-eys.input-error :messages="$errors->get('username')" />
                </div>
                <div class="ia-field">
                    <x-eys.label for="email" :value="__('eys.uye.column_email')" />
                    <x-eys.input id="email" type="email" name="email" :value="old('email')" autocomplete="off" />
                    <x-eys.input-error :messages="$errors->get('email')" />
                </div>
            </div>

            <div class="ip-grid-2">
                <div class="ia-field">
                    <x-eys.label for="phone_number" :value="__('eys.uye.phone_number')" />
                    <x-eys.input id="phone_number" type="text" name="phone_number" :value="old('phone_number')" autocomplete="off" />
                    <x-eys.input-error :messages="$errors->get('phone_number')" />
                </div>
                <div class="ia-field">
                    <x-eys.label for="education_level_id" :value="__('eys.uye.education_level')" />
                    <select id="education_level_id" name="education_level_id" class="ia-input">
                        <option value="">{{ __('eys.uye.education_level_none') }}</option>
                        @foreach ($educationLevels as $level)
                            <option value="{{ $level->id }}" @selected(old('education_level_id') === $level->id)>{{ $level->getTranslation()?->name }}</option>
                        @endforeach
                    </select>
                    <x-eys.input-error :messages="$errors->get('education_level_id')" />
                </div>
            </div>

            <div class="ip-grid-2">
                <div class="ia-field">
                    <x-eys.label for="uye_turu" :value="__('eys.uye.uye_turu')" />
                    <select id="uye_turu" name="uye_turu" class="ia-input">
                        @foreach ([0, 1, 2, 3] as $type)
                            <option value="{{ $type }}" @selected((int) old('uye_turu', 0) === $type)>{{ __('eys.uye.uye_turu_'.$type) }}</option>
                        @endforeach
                    </select>
                    <x-eys.input-error :messages="$errors->get('uye_turu')" />
                </div>
                <div class="ia-field">
                    <x-eys.label for="status" :value="__('eys.uye.status')" />
                    <select id="status" name="status" class="ia-input">
                        <option value="1" @selected((int) old('status', 1) === 1)>{{ __('eys.uye.status_1') }}</option>
                        <option value="0" @selected((int) old('status', 1) === 0)>{{ __('eys.uye.status_0') }}</option>
                        <option value="90" @selected((int) old('status', 1) === 90)>{{ __('eys.uye.status_90') }}</option>
                    </select>
                    <x-eys.input-error :messages="$errors->get('status')" />
                </div>
            </div>

            <div class="ip-grid-2">
                <div class="ia-field">
                    <x-eys.label for="password" :value="__('eys.uye.password')" />
                    <x-eys.input id="password" type="password" name="password" autocomplete="new-password" />
                    <x-eys.input-error :messages="$errors->get('password')" />
                </div>
                <div class="ia-field" style="margin-bottom: 0;">
                    <x-eys.label for="password_confirmation" :value="__('eys.uye.password_confirmation')" />
                    <x-eys.input id="password_confirmation" type="password" name="password_confirmation" autocomplete="new-password" />
                </div>
            </div>

            <div style="margin-top: 1.5rem;">
                <x-eys.button><x-eys.icon name="plus" />{{ __('eys.common.add') }}</x-eys.button>
            </div>
        </form>
    </div>
</x-eys.app-layout>
