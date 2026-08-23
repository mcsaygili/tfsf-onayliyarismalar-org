<x-eys.app-layout :title="__('eys.participant_approval_process.new')">
    <div class="ip-page-actions">
        <x-eys.breadcrumb :crumbs="[
            ['label' => __('eys.nav.dashboard'), 'url' => route('eys.dashboard')],
            ['label' => __('eys.nav.section_competition_system')],
            ['label' => __('eys.nav.reference_data')],
            ['label' => __('eys.participant_approval_process.title'), 'url' => route('eys.participant-approval-processes.index')],
            ['label' => __('eys.participant_approval_process.new')],
        ]" />
        <a href="{{ route('eys.participant-approval-processes.index') }}" class="ia-btn ia-btn-secondary ip-btn-sm"><x-eys.icon name="back" />{{ __('eys.common.back') }}</a>
    </div>
    <form method="POST" action="{{ route('eys.participant-approval-processes.store') }}" novalidate autocomplete="off">
        @csrf
        @include('eys.participant-approval-processes._form', ['process' => new App\Models\ParticipantApprovalProcess, 'locales' => $locales])
        <div class="ip-form-actions"><x-eys.button><x-eys.icon name="plus" />{{ __('eys.common.add') }}</x-eys.button></div>
    </form>
</x-eys.app-layout>
