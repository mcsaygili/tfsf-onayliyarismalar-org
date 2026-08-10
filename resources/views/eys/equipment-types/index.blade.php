<x-eys.app-layout :title="__('eys.equipment_type.title')">
    <div class="ip-panel-stack">
        <div style="display: flex; align-items: center; justify-content: space-between; gap: 1rem;">
            <x-eys.breadcrumb :crumbs="[
                ['label' => __('eys.nav.dashboard'), 'url' => route('eys.dashboard')],
                ['label' => __('eys.nav.equipment_catalog')],
                ['label' => __('eys.equipment_type.title')],
            ]" />
            <a href="{{ route('eys.equipment-types.create') }}" class="ia-btn"><x-eys.icon name="plus" />{{ __('eys.common.add') }}</a>
        </div>

        <x-eys.filter-panel :action="route('eys.equipment-types.index')" :reset-url="route('eys.equipment-types.index')" :total="$equipmentTypes->total()">
            <x-eys.filter-search name="name" :label="__('eys.equipment_type.filter_name')" :value="$filter['name']" />
            <div class="ip-filter-field">
                <label for="filter-status">{{ __('eys.equipment_type.filter_status') }}</label>
                <select id="filter-status" name="status" class="ia-input" onchange="this.form.submit()">
                    <option value="">{{ __('eys.equipment_type.filter_all_status') }}</option>
                    <option value="1" @selected($filter['status'] === '1')>{{ __('eys.equipment_type.status_active') }}</option>
                    <option value="0" @selected($filter['status'] === '0')>{{ __('eys.equipment_type.status_inactive') }}</option>
                </select>
            </div>
        </x-eys.filter-panel>

        <div class="ip-table-wrap">
            <table class="ip-table">
                <thead>
                    <tr>
                        <th>{{ __('eys.equipment_type.column_name') }}</th>
                        <th>{{ __('eys.equipment_type.column_order') }}</th>
                        <th>{{ __('eys.equipment_type.column_status') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($equipmentTypes as $equipmentType)
                        <tr>
                            <td class="ip-cell-name">{{ $equipmentType->getTranslation()?->name ?? '—' }}</td>
                            <td>{{ $equipmentType->sort_order }}</td>
                            <td>
                                @if ($equipmentType->status)
                                    <span class="ip-badge is-active">{{ __('eys.equipment_type.status_active') }}</span>
                                @else
                                    <span class="ip-badge is-inactive">{{ __('eys.equipment_type.status_inactive') }}</span>
                                @endif
                            </td>
                            <td style="text-align: right; white-space: nowrap;">
                                <a href="{{ route('eys.equipment-types.edit', $equipmentType) }}" class="ip-row-icon-btn" title="{{ __('eys.users.edit_action') }}" aria-label="{{ __('eys.users.edit_action') }}">
                                    <x-eys.icon name="edit" />
                                </a>
                                <form method="POST" action="{{ route('eys.equipment-types.destroy', $equipmentType) }}" style="display: inline;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="button" class="ip-row-icon-btn" title="{{ __('eys.equipment_type.delete_action') }}" aria-label="{{ __('eys.equipment_type.delete_action') }}" onclick="eysConfirm(@js(__('eys.equipment_type.delete_confirm')), this.closest('form'))">
                                        <x-eys.icon name="trash" />
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="ip-table-empty">{{ __('eys.equipment_type.empty') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $equipmentTypes->onEachSide(1)->links('vendor.pagination.eys') }}
    </div>
</x-eys.app-layout>
