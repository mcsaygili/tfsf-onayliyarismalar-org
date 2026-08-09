<x-eys.app-layout :title="__('eys.institution_type.new')">
    <div class="ip-page-actions" style="justify-content: space-between;">
        <x-eys.breadcrumb :crumbs="[
            ['label' => __('eys.nav.dashboard'), 'url' => route('eys.dashboard')],
            ['label' => __('eys.nav.reference_data')],
            ['label' => __('eys.institution_type.title'), 'url' => route('eys.institution-types.index')],
            ['label' => __('eys.institution_type.new')],
        ]" />
        <a href="{{ route('eys.institution-types.index') }}" class="ia-btn ia-btn-secondary ip-btn-sm">
            <x-eys.icon name="back" />
            {{ __('eys.common.back') }}
        </a>
    </div>

    <form method="POST" action="{{ route('eys.institution-types.store') }}" novalidate autocomplete="off">
        @csrf

        @include('eys.institution-types._form', [
            'institutionType' => new App\Models\InstitutionType,
            'locales' => $locales,
        ])

        <div style="margin-top: 1.5rem;">
            <x-eys.button><x-eys.icon name="plus" />{{ __('eys.common.add') }}</x-eys.button>
        </div>
    </form>
</x-eys.app-layout>
