<x-eys.app-layout :title="__('eys.institution_staff.title')">
    <div class="ip-panel-stack">
        <div style="display: flex; align-items: center; justify-content: space-between; gap: 1rem;">
            <x-eys.breadcrumb :crumbs="[
                ['label' => __('eys.nav.dashboard'), 'url' => route('eys.dashboard')],
                ['label' => __('eys.module_names.Institution'), 'url' => route('eys.institution.dashboard')],
                ['label' => __('eys.institution.title'), 'url' => route('eys.institutions.index')],
                ['label' => $institution->name ?? '—'],
            ]" />
            <a href="{{ route('eys.institution-staff.create', $institution) }}" class="ia-btn"><x-eys.icon name="plus" />{{ __('eys.common.add') }}</a>
        </div>

        <x-eys.filter-panel :action="route('eys.institution-staff.index', $institution)" :reset-url="route('eys.institution-staff.index', $institution)" :total="$staff->total()">
            <x-eys.filter-search name="name" :label="__('eys.institution_staff.filter_name')" :value="$filter['name']" />
            <div class="ip-filter-field">
                <label for="filter-status">{{ __('eys.institution_staff.filter_status') }}</label>
                <select id="filter-status" name="status" class="ia-input" onchange="this.form.submit()">
                    <option value="">{{ __('eys.institution_staff.filter_all_status') }}</option>
                    <option value="1" @selected($filter['status'] === '1')>{{ __('eys.institution_staff.status_active') }}</option>
                    <option value="0" @selected($filter['status'] === '0')>{{ __('eys.institution_staff.status_inactive') }}</option>
                </select>
            </div>
        </x-eys.filter-panel>

        <div class="ip-table-wrap">
            <table class="ip-table">
                <thead>
                    <tr>
                        <th>{{ __('eys.institution_staff.column_name') }}</th>
                        <th>{{ __('eys.institution_staff.column_email') }}</th>
                        <th>{{ __('eys.institution_staff.column_phone') }}</th>
                        <th>{{ __('eys.institution_staff.column_status') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($staff as $person)
                        <tr>
                            <td class="ip-cell-name">{{ trim(($person->first_name ?? '').' '.($person->last_name ?? '')) ?: '—' }}</td>
                            <td>{{ $person->email }}</td>
                            <td>{{ $person->phone ?: '—' }}</td>
                            <td>
                                @if ($person->status)
                                    <span class="ip-badge is-active">{{ __('eys.institution_staff.status_active') }}</span>
                                @else
                                    <span class="ip-badge is-inactive">{{ __('eys.institution_staff.status_inactive') }}</span>
                                @endif
                            </td>
                            <td style="text-align: right; white-space: nowrap;">
                                <a href="{{ route('eys.institution-staff.edit', [$institution, $person]) }}" class="ip-row-icon-btn" title="{{ __('eys.users.edit_action') }}" aria-label="{{ __('eys.users.edit_action') }}">
                                    <x-eys.icon name="edit" />
                                </a>
                                <form method="POST" action="{{ route('eys.institution-staff.destroy', [$institution, $person]) }}" style="display: inline;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="button" class="ip-row-icon-btn" title="{{ __('eys.institution_staff.delete_action') }}" aria-label="{{ __('eys.institution_staff.delete_action') }}" onclick="eysConfirm(@js(__('eys.institution_staff.delete_confirm')), this.closest('form'))">
                                        <x-eys.icon name="trash" />
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="ip-table-empty">{{ __('eys.institution_staff.empty') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $staff->onEachSide(1)->links('vendor.pagination.eys') }}
    </div>
</x-eys.app-layout>
