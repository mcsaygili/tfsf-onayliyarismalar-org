<x-eys.app-layout :title="__('eys.permission.edit_title')">
    <div class="ip-page-actions" style="justify-content: space-between;">
        <x-eys.breadcrumb :crumbs="[
            ['label' => __('eys.nav.dashboard'), 'url' => route('eys.dashboard')],
            ['label' => __('eys.permission.title'), 'url' => route('eys.permissions.index')],
            ['label' => __('eys.permission.edit_title')],
        ]" />
        <a href="{{ route('eys.permissions.index') }}" class="ia-btn ia-btn-secondary ip-btn-sm">
            <x-eys.icon name="back" />
            {{ __('eys.common.back') }}
        </a>
    </div>

    <form method="POST" action="{{ route('eys.permissions.update', $permission) }}" novalidate autocomplete="off">
        @csrf
        @method('PATCH')

        @include('eys.permissions._form', ['permission' => $permission, 'moduleLabels' => $moduleLabels])

        <div style="margin-top: 1.5rem;">
            <x-eys.button>{{ __('eys.common.update') }}</x-eys.button>
        </div>
    </form>
</x-eys.app-layout>
