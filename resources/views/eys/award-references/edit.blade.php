<x-eys.app-layout :title="__('eys.award_reference.edit_title')">
    <form method="POST" action="{{ route('eys.award-references.update', $award) }}" novalidate>@csrf @method('PATCH')
        <x-eys.breadcrumb :crumbs="[['label' => __('eys.nav.dashboard'), 'url' => route('eys.dashboard')], ['label' => __('eys.award_reference.title'), 'url' => route('eys.award-references.index')], ['label' => __('eys.award_reference.edit_title')]]" />
        @include('eys.award-references._form', ['award' => $award, 'locales' => $locales])
        <div class="ip-form-actions"><a href="{{ route('eys.award-references.index') }}" class="ia-btn ia-btn-secondary">{{ __('eys.common.cancel') }}</a><button type="submit" class="ia-btn">{{ __('eys.common.save') }}</button></div>
    </form>
</x-eys.app-layout>
