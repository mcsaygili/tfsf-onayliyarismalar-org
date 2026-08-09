<x-eys.app-layout :title="__('eys.institution_type.edit_title')">
    <div class="ip-page-actions" style="justify-content: space-between;">
        <x-eys.breadcrumb :crumbs="[
            ['label' => __('eys.nav.dashboard'), 'url' => route('eys.dashboard')],
            ['label' => __('eys.nav.reference_data')],
            ['label' => __('eys.institution_type.title'), 'url' => route('eys.institution-types.index')],
            ['label' => __('eys.institution_type.edit_title')],
        ]" />
        <a href="{{ route('eys.institution-types.index') }}" class="ia-btn ia-btn-secondary ip-btn-sm">
            <x-eys.icon name="back" />
            {{ __('eys.common.back') }}
        </a>
    </div>

    <form method="POST" action="{{ route('eys.institution-types.update', $institutionType) }}" novalidate autocomplete="off">
        @csrf
        @method('PATCH')

        @include('eys.institution-types._form', [
            'institutionType' => $institutionType,
            'locales' => $locales,
        ])

        <div style="margin-top: 1.5rem;">
            <x-eys.button>{{ __('eys.common.update') }}</x-eys.button>
        </div>
    </form>
</x-eys.app-layout>
