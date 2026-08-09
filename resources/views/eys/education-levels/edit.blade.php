<x-eys.app-layout :title="__('eys.education_level.edit_title')">
    <div class="ip-page-actions" style="justify-content: space-between;">
        <x-eys.breadcrumb :crumbs="[
            ['label' => __('eys.nav.dashboard'), 'url' => route('eys.dashboard')],
            ['label' => __('eys.nav.reference_data')],
            ['label' => __('eys.education_level.title'), 'url' => route('eys.education-levels.index')],
            ['label' => __('eys.education_level.edit_title')],
        ]" />
        <a href="{{ route('eys.education-levels.index') }}" class="ia-btn ia-btn-secondary ip-btn-sm">
            <x-eys.icon name="back" />
            {{ __('eys.common.back') }}
        </a>
    </div>

    <form method="POST" action="{{ route('eys.education-levels.update', $educationLevel) }}" novalidate autocomplete="off">
        @csrf
        @method('PATCH')

        @include('eys.education-levels._form', [
            'educationLevel' => $educationLevel,
            'locales' => $locales,
        ])

        <div style="margin-top: 1.5rem;">
            <x-eys.button>{{ __('eys.common.update') }}</x-eys.button>
        </div>
    </form>
</x-eys.app-layout>
