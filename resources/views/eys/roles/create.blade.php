<x-eys.app-layout :title="__('eys.role.new')">
    <div class="ip-page-actions" style="justify-content: space-between;">
        <x-eys.breadcrumb :crumbs="[
            ['label' => __('eys.nav.dashboard'), 'url' => route('eys.dashboard')],
            ['label' => __('eys.role.title'), 'url' => route('eys.roles.index')],
            ['label' => __('eys.role.new')],
        ]" />
        <a href="{{ route('eys.roles.index') }}" class="ia-btn ia-btn-secondary ip-btn-sm">
            <x-eys.icon name="back" />
            {{ __('eys.common.back') }}
        </a>
    </div>

    <form method="POST" action="{{ route('eys.roles.store') }}" novalidate autocomplete="off">
        @csrf

        @include('eys.roles._form', [
            'role' => new App\Models\Role,
            'moduleLabels' => $moduleLabels,
            'permissionsByModule' => $permissionsByModule,
            'selectedPermissions' => $selectedPermissions,
            'moduleEditable' => true,
        ])

        <div style="margin-top: 1.5rem;">
            <x-eys.button><x-eys.icon name="plus" />{{ __('eys.common.add') }}</x-eys.button>
        </div>
    </form>
</x-eys.app-layout>
