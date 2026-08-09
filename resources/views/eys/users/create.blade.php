<x-eys.app-layout :title="__('eys.users.create_title')">
    <div class="ip-page-actions" style="justify-content: space-between;">
        <x-eys.breadcrumb :crumbs="[
            ['label' => __('eys.nav.dashboard'), 'url' => route('eys.dashboard')],
            ['label' => __('eys.users.list_title'), 'url' => route('eys.users.index')],
            ['label' => __('eys.users.create_title')],
        ]" />
        <a href="{{ route('eys.users.index') }}" class="ia-btn ia-btn-secondary ip-btn-sm">
            <x-eys.icon name="back" />
            {{ __('eys.common.back') }}
        </a>
    </div>

    <div class="ip-card">
        <div class="ip-section-title">{{ __('eys.users.create_title') }}</div>
        <div class="ip-section-hint">{{ __('eys.users.create_hint') }}</div>

        <form method="POST" action="{{ route('eys.users.store') }}" novalidate autocomplete="off">
            @csrf

            <div class="ip-grid-2">
                <div class="ia-field">
                    <x-eys.label for="first_name" :value="__('eys.users.first_name')" />
                    <x-eys.input id="first_name" type="text" name="first_name" :value="old('first_name')" autocomplete="off" />
                    <x-eys.input-error :messages="$errors->get('first_name')" />
                </div>
                <div class="ia-field">
                    <x-eys.label for="last_name" :value="__('eys.users.last_name')" />
                    <x-eys.input id="last_name" type="text" name="last_name" :value="old('last_name')" autocomplete="off" />
                    <x-eys.input-error :messages="$errors->get('last_name')" />
                </div>
            </div>

            <div class="ia-field">
                <x-eys.label for="email" :value="__('eys.users.column_email')" />
                <x-eys.input id="email" type="email" name="email" :value="old('email')" autocomplete="off" />
                <x-eys.input-error :messages="$errors->get('email')" />
            </div>

            <div class="ia-field">
                <x-eys.label for="phone" :value="__('eys.users.phone')" />
                <x-eys.input id="phone" type="text" name="phone" :value="old('phone')" autocomplete="off" />
                <x-eys.input-error :messages="$errors->get('phone')" />
            </div>

            <div class="ip-grid-2">
                <div class="ia-field">
                    <x-eys.label for="password" :value="__('eys.users.password')" />
                    <x-eys.input id="password" type="password" name="password" autocomplete="new-password" />
                    <x-eys.input-error :messages="$errors->get('password')" />
                </div>
                <div class="ia-field" style="margin-bottom: 0;">
                    <x-eys.label for="password_confirmation" :value="__('eys.users.password_confirmation')" />
                    <x-eys.input id="password_confirmation" type="password" name="password_confirmation" autocomplete="new-password" />
                </div>
            </div>

            <div style="margin-top: 1.5rem;">
                <x-eys.button><x-eys.icon name="plus" />{{ __('eys.common.add') }}</x-eys.button>
            </div>
        </form>
    </div>
</x-eys.app-layout>
