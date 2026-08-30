<x-eys.app-layout :title="__('eys.competition_type.edit_title')">
    <div class="ip-page-actions">
        <x-eys.breadcrumb :crumbs="[
            ['label' => __('eys.nav.dashboard'), 'url' => route('eys.dashboard')],
            ['label' => __('eys.nav.section_competition_system')],
            ['label' => __('eys.nav.reference_data')],
            ['label' => __('eys.competition_type.title'), 'url' => route('eys.competition-types.index')],
            ['label' => __('eys.competition_type.edit_title')],
        ]" />
        <a href="{{ route('eys.competition-types.index') }}" class="ia-btn ia-btn-secondary ip-btn-sm">
            <x-eys.icon name="back" />{{ __('eys.common.back') }}
        </a>
    </div>

    <form method="POST" action="{{ route('eys.competition-types.update', $competitionType) }}" novalidate autocomplete="off">
        @csrf
        @method('PATCH')
        @include('eys.competition-types._form', [
            'competitionType' => $competitionType,
            'locales' => $locales,
            'iconOptions' => $iconOptions,
        ])

        <div class="ip-form-actions">
            <x-eys.button>{{ __('eys.common.update') }}</x-eys.button>
        </div>
    </form>
</x-eys.app-layout>
