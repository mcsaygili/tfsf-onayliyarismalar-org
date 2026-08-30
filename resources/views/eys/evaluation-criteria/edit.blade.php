<x-eys.app-layout :title="__('eys.evaluation_criterion.edit_title')">
    <form method="POST" action="{{ route('eys.evaluation-criteria.update', $criterion) }}" novalidate>@csrf @method('PATCH')
        <x-eys.breadcrumb :crumbs="[['label' => __('eys.nav.dashboard'), 'url' => route('eys.dashboard')], ['label' => __('eys.evaluation_criterion.title'), 'url' => route('eys.evaluation-criteria.index')], ['label' => __('eys.evaluation_criterion.edit_title')]]" />
        @include('eys.evaluation-criteria._form', ['criterion' => $criterion, 'locales' => $locales])
        <div class="ip-form-actions"><a href="{{ route('eys.evaluation-criteria.index') }}" class="ia-btn ia-btn-secondary">{{ __('eys.common.cancel') }}</a><button type="submit" class="ia-btn">{{ __('eys.common.save') }}</button></div>
    </form>
</x-eys.app-layout>
