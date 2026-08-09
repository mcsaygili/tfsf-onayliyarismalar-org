<x-eys.app-layout :title="__('eys.city.edit_title')">
    <div class="ip-page-actions" style="justify-content: space-between;">
        <x-eys.breadcrumb :crumbs="[
            ['label' => __('eys.nav.dashboard'), 'url' => route('eys.dashboard')],
            ['label' => __('eys.nav.reference_data')],
            ['label' => __('eys.city.title'), 'url' => route('eys.cities.index')],
            ['label' => __('eys.city.edit_title')],
        ]" />
        <a href="{{ route('eys.cities.index') }}" class="ia-btn ia-btn-secondary ip-btn-sm">
            <x-eys.icon name="back" />
            {{ __('eys.common.back') }}
        </a>
    </div>

    <form method="POST" action="{{ route('eys.cities.update', $city) }}" novalidate autocomplete="off">
        @csrf
        @method('PATCH')

        @include('eys.cities._form', [
            'city' => $city,
            'locales' => $locales,
            'countryOptions' => $countryOptions,
        ])

        <div style="margin-top: 1.5rem;">
            <x-eys.button>{{ __('eys.common.update') }}</x-eys.button>
        </div>
    </form>
</x-eys.app-layout>
