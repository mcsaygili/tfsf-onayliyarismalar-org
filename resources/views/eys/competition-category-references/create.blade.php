@php($title = __("eys.$translation.title"))
@php($newTitle = __("eys.$translation.new"))
<x-eys.app-layout :title="$newTitle">
    <x-eys.breadcrumb :crumbs="[['label' => __('eys.nav.dashboard'), 'url' => route('eys.dashboard')], ['label' => $title, 'url' => route($route.'.index')], ['label' => $newTitle]]" />
    <form method="POST" action="{{ route($route.'.store') }}" novalidate>@csrf
        @include('eys.competition-category-references._form')
        <div class="ip-form-actions"><a href="{{ route($route.'.index') }}" class="ia-btn ia-btn-secondary">{{ __('eys.common.cancel') }}</a><button class="ia-btn">{{ __('eys.common.save') }}</button></div>
    </form>
</x-eys.app-layout>
