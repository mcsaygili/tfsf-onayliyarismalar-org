<x-eys.app-layout :title="__('eys.participant_approval_process.title')">
    <div class="ip-panel-stack">
        <div class="ip-page-actions">
            <x-eys.breadcrumb :crumbs="[
                ['label' => __('eys.nav.dashboard'), 'url' => route('eys.dashboard')],
                ['label' => __('eys.nav.section_competition_system')],
                ['label' => __('eys.nav.reference_data')],
                ['label' => __('eys.participant_approval_process.title')],
            ]" />
            <a href="{{ route('eys.participant-approval-processes.create') }}" class="ia-btn"><x-eys.icon name="plus" />{{ __('eys.common.add') }}</a>
        </div>

        <p class="ip-section-hint">{{ __('eys.participant_approval_process.list_hint') }}</p>

        <x-eys.filter-panel :action="route('eys.participant-approval-processes.index')" :reset-url="route('eys.participant-approval-processes.index')" :total="$processes->total()">
            <x-eys.filter-search name="name" :label="__('eys.participant_approval_process.filter_name')" :value="$filter['name']" />
            <div class="ip-filter-field">
                <label for="filter-status">{{ __('eys.participant_approval_process.filter_status') }}</label>
                <select id="filter-status" name="status" class="ia-input" onchange="this.form.submit()">
                    <option value="">{{ __('eys.participant_approval_process.filter_all_status') }}</option>
                    <option value="1" @selected($filter['status'] === '1')>{{ __('eys.participant_approval_process.status_active') }}</option>
                    <option value="0" @selected($filter['status'] === '0')>{{ __('eys.participant_approval_process.status_inactive') }}</option>
                </select>
            </div>
        </x-eys.filter-panel>

        <div class="ip-table-wrap">
            <table class="ip-table">
                <thead><tr>
                    <th>{{ __('eys.participant_approval_process.column_name') }}</th>
                    <th>{{ __('eys.participant_approval_process.column_code') }}</th>
                    <th>{{ __('eys.participant_approval_process.column_order') }}</th>
                    <th>{{ __('eys.participant_approval_process.column_status') }}</th>
                    <th></th>
                </tr></thead>
                <tbody>
                    @forelse ($processes as $process)
                        <tr>
                            <td class="ip-cell-name">{{ $process->getTranslation()?->name ?? '—' }}</td>
                            <td><code>{{ $process->code }}</code></td>
                            <td>{{ $process->sort_order }}</td>
                            <td><span class="ip-badge {{ $process->status ? 'is-active' : 'is-inactive' }}">{{ $process->status ? __('eys.participant_approval_process.status_active') : __('eys.participant_approval_process.status_inactive') }}</span></td>
                            <td class="ip-table-actions">
                                <a href="{{ route('eys.participant-approval-processes.edit', $process) }}" class="ip-row-icon-btn" title="{{ __('eys.users.edit_action') }}" aria-label="{{ __('eys.users.edit_action') }}"><x-eys.icon name="edit" /></a>
                                <form method="POST" action="{{ route('eys.participant-approval-processes.destroy', $process) }}" class="ip-inline-form">
                                    @csrf @method('DELETE')
                                    <button type="button" class="ip-row-icon-btn" title="{{ __('eys.participant_approval_process.delete_action') }}" aria-label="{{ __('eys.participant_approval_process.delete_action') }}" onclick="eysConfirm(@js(__('eys.participant_approval_process.delete_confirm')), this.closest('form'))"><x-eys.icon name="trash" /></button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="ip-table-empty">{{ __('eys.participant_approval_process.empty') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $processes->onEachSide(1)->links('vendor.pagination.eys') }}
    </div>
</x-eys.app-layout>
