<x-eys.app-layout :title="__('eys.photo_technique.edit_title')">
    <div class="ip-page-actions" style="justify-content: space-between;">
        <x-eys.breadcrumb :crumbs="[
            ['label' => __('eys.nav.dashboard'), 'url' => route('eys.dashboard')],
            ['label' => __('eys.nav.reference_data')],
            ['label' => __('eys.photo_technique.title'), 'url' => route('eys.photo-techniques.index')],
            ['label' => __('eys.photo_technique.edit_title')],
        ]" />
        <a href="{{ route('eys.photo-techniques.index') }}" class="ia-btn ia-btn-secondary ip-btn-sm">
            <x-eys.icon name="back" />
            {{ __('eys.common.back') }}
        </a>
    </div>

    <form method="POST" action="{{ route('eys.photo-techniques.update', $photoTechnique) }}" novalidate autocomplete="off">
        @csrf
        @method('PATCH')

        @include('eys.photo-techniques._form', [
            'photoTechnique' => $photoTechnique,
            'locales' => $locales,
        ])

        <div style="margin-top: 1.5rem;">
            <x-eys.button>{{ __('eys.common.update') }}</x-eys.button>
        </div>
    </form>
</x-eys.app-layout>
