<x-eys.app-layout :title="__('eys.photo_category.new')">
    <div class="ip-page-actions" style="justify-content: space-between;">
        <x-eys.breadcrumb :crumbs="[
            ['label' => __('eys.nav.dashboard'), 'url' => route('eys.dashboard')],
            ['label' => __('eys.nav.reference_data')],
            ['label' => __('eys.photo_category.title'), 'url' => route('eys.photo-categories.index')],
            ['label' => __('eys.photo_category.new')],
        ]" />
        <a href="{{ route('eys.photo-categories.index') }}" class="ia-btn ia-btn-secondary ip-btn-sm">
            <x-eys.icon name="back" />
            {{ __('eys.common.back') }}
        </a>
    </div>

    <form method="POST" action="{{ route('eys.photo-categories.store') }}" novalidate autocomplete="off">
        @csrf

        @include('eys.photo-categories._form', [
            'photoCategory' => new App\Models\PhotoCategory,
            'locales' => $locales,
        ])

        <div style="margin-top: 1.5rem;">
            <x-eys.button><x-eys.icon name="plus" />{{ __('eys.common.add') }}</x-eys.button>
        </div>
    </form>
</x-eys.app-layout>
