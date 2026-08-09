<x-eys.app-layout :title="__('eys.institution.title')">
    <div class="ip-panel-stack">
        <div style="display: flex; align-items: center; justify-content: space-between; gap: 1rem;">
            <x-eys.breadcrumb :crumbs="[
                ['label' => __('eys.nav.dashboard'), 'url' => route('eys.dashboard')],
                ['label' => __('eys.module_names.Institution'), 'url' => route('eys.institution.dashboard')],
                ['label' => __('eys.institution.title')],
            ]" />
            <a href="{{ route('eys.institutions.create') }}" class="ia-btn"><x-eys.icon name="plus" />{{ __('eys.common.add') }}</a>
        </div>

        <x-eys.filter-panel :action="route('eys.institutions.index')" :reset-url="route('eys.institutions.index')" :total="$institutions->total()">
            <x-eys.filter-search name="name" :label="__('eys.institution.filter_name')" :value="$filter['name']" />
            <div class="ip-filter-field">
                <label for="filter-status">{{ __('eys.institution.filter_status') }}</label>
                <select id="filter-status" name="status" class="ia-input" onchange="this.form.submit()">
                    <option value="">{{ __('eys.institution.filter_all_status') }}</option>
                    <option value="1" @selected($filter['status'] === '1')>{{ __('eys.institution.status_active') }}</option>
                    <option value="0" @selected($filter['status'] === '0')>{{ __('eys.institution.status_inactive') }}</option>
                </select>
            </div>
        </x-eys.filter-panel>

        <div class="ip-table-wrap">
            <table class="ip-table">
                <thead>
                    <tr>
                        <th>{{ __('eys.institution.column_name') }}</th>
                        <th>{{ __('eys.institution.column_type') }}</th>
                        <th>{{ __('eys.institution.column_staff_count') }}</th>
                        <th>{{ __('eys.institution.column_status') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($institutions as $institution)
                        <tr>
                            <td class="ip-cell-name">{{ $institution->name ?? '—' }}</td>
                            <td>{{ $institution->institutionType?->getTranslation()?->name ?? '—' }}</td>
                            <td>{{ $institution->staff_count }}</td>
                            <td>
                                @if ($institution->status)
                                    <span class="ip-badge is-active">{{ __('eys.institution.status_active') }}</span>
                                @else
                                    <span class="ip-badge is-inactive">{{ __('eys.institution.status_inactive') }}</span>
                                @endif
                            </td>
                            <td style="text-align: right; white-space: nowrap;">
                                <a href="{{ route('eys.institution-staff.index', $institution) }}" class="ip-row-icon-btn" title="{{ __('eys.institution.staff_action') }}" aria-label="{{ __('eys.institution.staff_action') }}">
                                    <x-eys.icon name="staff" />
                                </a>
                                <a href="{{ route('eys.institutions.edit', $institution) }}" class="ip-row-icon-btn" title="{{ __('eys.users.edit_action') }}" aria-label="{{ __('eys.users.edit_action') }}">
                                    <x-eys.icon name="edit" />
                                </a>
                                <form method="POST" action="{{ route('eys.institutions.destroy', $institution) }}" style="display: inline;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="button" class="ip-row-icon-btn" title="{{ __('eys.institution.delete_action') }}" aria-label="{{ __('eys.institution.delete_action') }}" onclick="eysConfirm(@js(__('eys.institution.delete_confirm')), this.closest('form'))">
                                        <x-eys.icon name="trash" />
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="ip-table-empty">{{ __('eys.institution.empty') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $institutions->onEachSide(1)->links('vendor.pagination.eys') }}
    </div>
</x-eys.app-layout>
