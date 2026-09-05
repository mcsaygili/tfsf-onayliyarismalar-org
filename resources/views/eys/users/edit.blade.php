<x-eys.app-layout :title="__('eys.users.edit_title')">
    <div class="ip-page-actions" style="justify-content: space-between;">
        <x-eys.breadcrumb :crumbs="[
            ['label' => __('eys.nav.dashboard'), 'url' => route('eys.dashboard')],
            ['label' => __('eys.users.list_title'), 'url' => route($backRoute)],
            ['label' => __('eys.users.edit_title')],
        ]" />
        <a href="{{ route($backRoute) }}" class="ia-btn ia-btn-secondary ip-btn-sm">
            <x-eys.icon name="back" />
            {{ __('eys.common.back') }}
        </a>
    </div>

    <div class="ip-card">
        <div class="ip-section-title">{{ __('eys.users.edit_title') }}</div>
        <div class="ip-section-hint">{{ __('eys.users.edit_hint') }}</div>

        <form method="POST" action="{{ route('eys.users.update', $user) }}" novalidate autocomplete="off">
            @csrf
            @method('PATCH')

            <div class="ip-grid-2">
                <div class="ia-field">
                    <x-eys.label for="first_name" :value="__('eys.users.first_name')" />
                    <x-eys.input id="first_name" type="text" name="first_name" :value="old('first_name', $user->first_name)" autocomplete="off" />
                    <x-eys.input-error :messages="$errors->get('first_name')" />
                </div>
                <div class="ia-field">
                    <x-eys.label for="last_name" :value="__('eys.users.last_name')" />
                    <x-eys.input id="last_name" type="text" name="last_name" :value="old('last_name', $user->last_name)" autocomplete="off" />
                    <x-eys.input-error :messages="$errors->get('last_name')" />
                </div>
            </div>

            <div class="ia-field">
                <x-eys.label for="email" :value="__('eys.users.column_email')" />
                <x-eys.input id="email" type="email" name="email" :value="old('email', $user->email)" :readonly="! $self && ! $canManageIdentity" autocomplete="off" />
                <x-eys.input-error :messages="$errors->get('email')" />
            </div>

            <div class="ia-field">
                <x-eys.label for="phone" :value="__('eys.users.phone')" />
                <x-eys.input id="phone" type="text" name="phone" :value="old('phone', $user->phone)" autocomplete="off" />
                <x-eys.input-error :messages="$errors->get('phone')" />
            </div>

            @if ($self)
                <div class="ia-field">
                    <x-eys.label for="current_password" :value="__('auth.current_password_for_email')" />
                    <x-eys.input id="current_password" type="password" name="current_password" autocomplete="current-password" />
                    <x-eys.input-error :messages="$errors->get('current_password')" />
                </div>
            @endif
            @if ($canManageIdentity)
            <div class="ia-field" style="margin-bottom: 0;" x-data="{ active: {{ old('status', (int) $user->status) ? 'true' : 'false' }} }">
                <x-eys.label :value="__('eys.users.column_status')" />
                <label class="ip-switch">
                    <input type="hidden" name="status" :value="active ? 1 : 0">
                    <input type="checkbox" class="ip-switch-checkbox" x-model="active">
                    <span class="ip-switch-track"><span class="ip-switch-thumb"></span></span>
                    <span class="ip-switch-label" x-text="active ? @js(__('eys.users.status_active')) : @js(__('eys.users.status_inactive'))"></span>
                </label>
                <x-eys.input-error :messages="$errors->get('status')" />
            </div>

            @endif

            <div style="margin-top: 1.5rem;">
                <x-eys.button>{{ __('eys.common.update') }}</x-eys.button>
            </div>
        </form>
    </div>
</x-eys.app-layout>
