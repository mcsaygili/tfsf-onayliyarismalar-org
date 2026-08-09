<x-eys.app-layout :title="__('eys.country.new')">
    <div class="ip-page-actions" style="justify-content: space-between;">
        <x-eys.breadcrumb :crumbs="[
            ['label' => __('eys.nav.dashboard'), 'url' => route('eys.dashboard')],
            ['label' => __('eys.nav.reference_data')],
            ['label' => __('eys.country.title'), 'url' => route('eys.countries.index')],
            ['label' => __('eys.country.new')],
        ]" />
        <a href="{{ route('eys.countries.index') }}" class="ia-btn ia-btn-secondary ip-btn-sm">
            <x-eys.icon name="back" />
            {{ __('eys.common.back') }}
        </a>
    </div>

    <form method="POST" action="{{ route('eys.countries.store') }}" novalidate autocomplete="off">
        @csrf

        @include('eys.countries._form', [
            'country' => new App\Models\Country,
            'locales' => $locales,
        ])

        <div style="margin-top: 1.5rem;">
            <x-eys.button><x-eys.icon name="plus" />{{ __('eys.common.add') }}</x-eys.button>
        </div>
    </form>
</x-eys.app-layout>
