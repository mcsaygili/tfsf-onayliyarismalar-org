<x-eys.app-layout :title="__('eys.equipment_model.edit_title')">
    <div class="ip-page-actions" style="justify-content: space-between;">
        <x-eys.breadcrumb :crumbs="[
            ['label' => __('eys.nav.dashboard'), 'url' => route('eys.dashboard')],
            ['label' => __('eys.nav.equipment_catalog')],
            ['label' => __('eys.equipment_model.title'), 'url' => route('eys.equipment-models.index')],
            ['label' => __('eys.equipment_model.edit_title')],
        ]" />
        <a href="{{ route('eys.equipment-models.index') }}" class="ia-btn ia-btn-secondary ip-btn-sm">
            <x-eys.icon name="back" />
            {{ __('eys.common.back') }}
        </a>
    </div>

    <form method="POST" action="{{ route('eys.equipment-models.update', $equipmentModel) }}" novalidate autocomplete="off">
        @csrf
        @method('PATCH')

        @include('eys.equipment-models._form', [
            'equipmentModel' => $equipmentModel,
            'brandOptions' => $brandOptions,
            'typeOptions' => $typeOptions,
        ])

        <div style="margin-top: 1.5rem;">
            <x-eys.button>{{ __('eys.common.update') }}</x-eys.button>
        </div>
    </form>
</x-eys.app-layout>
