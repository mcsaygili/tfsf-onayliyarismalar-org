<x-eys.app-layout :title="__('eys.role.edit_title')">
    <div class="ip-page-actions" style="justify-content: space-between;">
        <x-eys.breadcrumb :crumbs="[
            ['label' => __('eys.nav.dashboard'), 'url' => route('eys.dashboard')],
            ['label' => __('eys.role.title'), 'url' => route('eys.roles.index')],
            ['label' => __('eys.role.edit_title')],
        ]" />
        <a href="{{ route('eys.roles.index') }}" class="ia-btn ia-btn-secondary ip-btn-sm">
            <x-eys.icon name="back" />
            {{ __('eys.common.back') }}
        </a>
    </div>

    <form method="POST" action="{{ route('eys.roles.update', $role) }}" novalidate autocomplete="off">
        @csrf
        @method('PATCH')

        @include('eys.roles._form', [
            'role' => $role,
            'moduleLabels' => $moduleLabels,
            'permissionsByModule' => $permissionsByModule,
            'selectedPermissions' => $selectedPermissions,
            'moduleEditable' => false,
            'fixedModuleName' => $module->name,
        ])

        <div style="margin-top: 1.5rem;">
            <x-eys.button>{{ __('eys.common.update') }}</x-eys.button>
        </div>
    </form>
</x-eys.app-layout>
