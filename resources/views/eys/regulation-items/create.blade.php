<x-eys.app-layout :title="__('eys.regulation_item.new')">
    <div class="ip-page-actions" style="justify-content: space-between;">
        <x-eys.breadcrumb :crumbs="[
            ['label' => __('eys.nav.dashboard'), 'url' => route('eys.dashboard')],
            ['label' => __('eys.nav.section_competition_system')],
            ['label' => __('eys.regulation_item.title'), 'url' => route('eys.regulation-items.index')],
            ['label' => __('eys.regulation_item.new')],
        ]" />
        <a href="{{ route('eys.regulation-items.index') }}" class="ia-btn ia-btn-secondary ip-btn-sm">
            <x-eys.icon name="back" />
            {{ __('eys.common.back') }}
        </a>
    </div>

    <form method="POST" action="{{ route('eys.regulation-items.store') }}" novalidate autocomplete="off">
        @csrf

        @include('eys.regulation-items._form', [
            'item' => new App\Models\RegulationItem,
            'sections' => $sections,
            'locales' => $locales,
        ])

        <div style="margin-top: 1.5rem;">
            <x-eys.button><x-eys.icon name="plus" />{{ __('eys.common.add') }}</x-eys.button>
        </div>
    </form>
</x-eys.app-layout>
