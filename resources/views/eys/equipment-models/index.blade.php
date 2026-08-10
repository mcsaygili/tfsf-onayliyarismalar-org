<x-eys.app-layout :title="__('eys.equipment_model.title')">
    <div class="ip-panel-stack">
        <div style="display: flex; align-items: center; justify-content: space-between; gap: 1rem;">
            <x-eys.breadcrumb :crumbs="[
                ['label' => __('eys.nav.dashboard'), 'url' => route('eys.dashboard')],
                ['label' => __('eys.nav.equipment_catalog')],
                ['label' => __('eys.equipment_model.title')],
            ]" />
            <a href="{{ route('eys.equipment-models.create') }}" class="ia-btn"><x-eys.icon name="plus" />{{ __('eys.common.add') }}</a>
        </div>

        <x-eys.filter-panel :action="route('eys.equipment-models.index')" :reset-url="route('eys.equipment-models.index')" :total="$equipmentModels->total()">
            <x-eys.filter-search name="name" :label="__('eys.equipment_model.filter_name')" :value="$filter['name']" />
            <div class="ip-filter-field">
                <label for="filter-brand">{{ __('eys.equipment_model.filter_brand') }}</label>
                <select id="filter-brand" name="equipment_brand_id" class="ia-input" onchange="this.form.submit()">
                    <option value="">{{ __('eys.equipment_model.filter_all_brands') }}</option>
                    @foreach ($brandOptions as $id => $label)
                        <option value="{{ $id }}" @selected($filter['equipment_brand_id'] === $id)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="ip-filter-field">
                <label for="filter-type">{{ __('eys.equipment_model.filter_type') }}</label>
                <select id="filter-type" name="equipment_type_id" class="ia-input" onchange="this.form.submit()">
                    <option value="">{{ __('eys.equipment_model.filter_all_types') }}</option>
                    @foreach ($typeOptions as $id => $label)
                        <option value="{{ $id }}" @selected($filter['equipment_type_id'] === $id)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="ip-filter-field">
                <label for="filter-status">{{ __('eys.equipment_model.filter_status') }}</label>
                <select id="filter-status" name="status" class="ia-input" onchange="this.form.submit()">
                    <option value="">{{ __('eys.equipment_model.filter_all_status') }}</option>
                    <option value="1" @selected($filter['status'] === '1')>{{ __('eys.equipment_model.status_active') }}</option>
                    <option value="0" @selected($filter['status'] === '0')>{{ __('eys.equipment_model.status_inactive') }}</option>
                </select>
            </div>
        </x-eys.filter-panel>

        <div class="ip-table-wrap">
            <table class="ip-table">
                <thead>
                    <tr>
                        <th>{{ __('eys.equipment_model.column_name') }}</th>
                        <th>{{ __('eys.equipment_model.column_brand') }}</th>
                        <th>{{ __('eys.equipment_model.column_type') }}</th>
                        <th>{{ __('eys.equipment_model.column_status') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($equipmentModels as $equipmentModel)
                        <tr>
                            <td class="ip-cell-name">{{ $equipmentModel->name }}</td>
                            <td>{{ $equipmentModel->brand?->name ?? '—' }}</td>
                            <td>{{ $equipmentModel->type?->getTranslation()?->name ?? '—' }}</td>
                            <td>
                                @if ($equipmentModel->status)
                                    <span class="ip-badge is-active">{{ __('eys.equipment_model.status_active') }}</span>
                                @else
                                    <span class="ip-badge is-inactive">{{ __('eys.equipment_model.status_inactive') }}</span>
                                @endif
                            </td>
                            <td style="text-align: right; white-space: nowrap;">
                                <a href="{{ route('eys.equipment-models.edit', $equipmentModel) }}" class="ip-row-icon-btn" title="{{ __('eys.users.edit_action') }}" aria-label="{{ __('eys.users.edit_action') }}">
                                    <x-eys.icon name="edit" />
                                </a>
                                <form method="POST" action="{{ route('eys.equipment-models.destroy', $equipmentModel) }}" style="display: inline;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="button" class="ip-row-icon-btn" title="{{ __('eys.equipment_model.delete_action') }}" aria-label="{{ __('eys.equipment_model.delete_action') }}" onclick="eysConfirm(@js(__('eys.equipment_model.delete_confirm')), this.closest('form'))">
                                        <x-eys.icon name="trash" />
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="ip-table-empty">{{ __('eys.equipment_model.empty') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $equipmentModels->onEachSide(1)->links('vendor.pagination.eys') }}
    </div>
</x-eys.app-layout>
