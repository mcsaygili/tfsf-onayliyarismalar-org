<x-eys.app-layout :title="__('eys.regulation_item.edit_title')">
    <div class="ip-page-actions" style="justify-content: space-between;">
        <x-eys.breadcrumb :crumbs="[
            ['label' => __('eys.nav.dashboard'), 'url' => route('eys.dashboard')],
            ['label' => __('eys.nav.section_competition_system')],
            ['label' => __('eys.regulation_item.title'), 'url' => route('eys.regulation-items.index')],
            ['label' => __('eys.regulation_item.edit_title')],
        ]" />
        <a href="{{ route('eys.regulation-items.index') }}" class="ia-btn ia-btn-secondary ip-btn-sm">
            <x-eys.icon name="back" />
            {{ __('eys.common.back') }}
        </a>
    </div>

    <form method="POST" action="{{ route('eys.regulation-items.update', $item) }}" novalidate autocomplete="off">
        @csrf
        @method('PATCH')

        @include('eys.regulation-items._form', [
            'item' => $item,
            'sections' => $sections,
            'locales' => $locales,
        ])

        <div style="margin-top: 1.5rem;">
            <x-eys.button>{{ __('eys.common.update') }}</x-eys.button>
        </div>
    </form>
</x-eys.app-layout>
