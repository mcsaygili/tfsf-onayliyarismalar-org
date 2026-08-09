<x-eys.app-layout :title="__('eys.institution.new')">
    <div class="ip-page-actions" style="justify-content: space-between;">
        <x-eys.breadcrumb :crumbs="[
            ['label' => __('eys.nav.dashboard'), 'url' => route('eys.dashboard')],
            ['label' => __('eys.module_names.Institution'), 'url' => route('eys.institution.dashboard')],
            ['label' => __('eys.institution.title'), 'url' => route('eys.institutions.index')],
            ['label' => __('eys.institution.new')],
        ]" />
        <a href="{{ route('eys.institutions.index') }}" class="ia-btn ia-btn-secondary ip-btn-sm">
            <x-eys.icon name="back" />
            {{ __('eys.common.back') }}
        </a>
    </div>

    <form method="POST" action="{{ route('eys.institutions.store') }}" novalidate autocomplete="off">
        @csrf

        @include('eys.institutions._form', [
            'institution' => new App\Models\Institution,
            'institutionTypes' => $institutionTypes,
        ])

        <div style="margin-top: 1.5rem;">
            <x-eys.button><x-eys.icon name="plus" />{{ __('eys.common.add') }}</x-eys.button>
        </div>
    </form>
</x-eys.app-layout>
