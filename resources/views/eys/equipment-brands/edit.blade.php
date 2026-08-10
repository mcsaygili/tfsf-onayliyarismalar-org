<x-eys.app-layout :title="__('eys.equipment_brand.edit_title')">
    <div class="ip-page-actions" style="justify-content: space-between;">
        <x-eys.breadcrumb :crumbs="[
            ['label' => __('eys.nav.dashboard'), 'url' => route('eys.dashboard')],
            ['label' => __('eys.nav.equipment_catalog')],
            ['label' => __('eys.equipment_brand.title'), 'url' => route('eys.equipment-brands.index')],
            ['label' => __('eys.equipment_brand.edit_title')],
        ]" />
        <a href="{{ route('eys.equipment-brands.index') }}" class="ia-btn ia-btn-secondary ip-btn-sm">
            <x-eys.icon name="back" />
            {{ __('eys.common.back') }}
        </a>
    </div>

    <form method="POST" action="{{ route('eys.equipment-brands.update', $equipmentBrand) }}" novalidate autocomplete="off">
        @csrf
        @method('PATCH')

        @include('eys.equipment-brands._form', [
            'equipmentBrand' => $equipmentBrand,
        ])

        <div style="margin-top: 1.5rem;">
            <x-eys.button>{{ __('eys.common.update') }}</x-eys.button>
        </div>
    </form>
</x-eys.app-layout>
