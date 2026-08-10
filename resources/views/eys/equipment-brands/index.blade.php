<x-eys.app-layout :title="__('eys.equipment_brand.title')">
    <div class="ip-panel-stack">
        <div style="display: flex; align-items: center; justify-content: space-between; gap: 1rem;">
            <x-eys.breadcrumb :crumbs="[
                ['label' => __('eys.nav.dashboard'), 'url' => route('eys.dashboard')],
                ['label' => __('eys.nav.equipment_catalog')],
                ['label' => __('eys.equipment_brand.title')],
            ]" />
            <a href="{{ route('eys.equipment-brands.create') }}" class="ia-btn"><x-eys.icon name="plus" />{{ __('eys.common.add') }}</a>
        </div>

        <x-eys.filter-panel :action="route('eys.equipment-brands.index')" :reset-url="route('eys.equipment-brands.index')" :total="$equipmentBrands->total()">
            <x-eys.filter-search name="name" :label="__('eys.equipment_brand.filter_name')" :value="$filter['name']" />
            <div class="ip-filter-field">
                <label for="filter-status">{{ __('eys.equipment_brand.filter_status') }}</label>
                <select id="filter-status" name="status" class="ia-input" onchange="this.form.submit()">
                    <option value="">{{ __('eys.equipment_brand.filter_all_status') }}</option>
                    <option value="1" @selected($filter['status'] === '1')>{{ __('eys.equipment_brand.status_active') }}</option>
                    <option value="0" @selected($filter['status'] === '0')>{{ __('eys.equipment_brand.status_inactive') }}</option>
                </select>
            </div>
        </x-eys.filter-panel>

        <div class="ip-table-wrap">
            <table class="ip-table">
                <thead>
                    <tr>
                        <th>{{ __('eys.equipment_brand.column_name') }}</th>
                        <th>{{ __('eys.equipment_brand.column_status') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($equipmentBrands as $equipmentBrand)
                        <tr>
                            <td class="ip-cell-name">{{ $equipmentBrand->name }}</td>
                            <td>
                                @if ($equipmentBrand->status)
                                    <span class="ip-badge is-active">{{ __('eys.equipment_brand.status_active') }}</span>
                                @else
                                    <span class="ip-badge is-inactive">{{ __('eys.equipment_brand.status_inactive') }}</span>
                                @endif
                            </td>
                            <td style="text-align: right; white-space: nowrap;">
                                <a href="{{ route('eys.equipment-brands.edit', $equipmentBrand) }}" class="ip-row-icon-btn" title="{{ __('eys.users.edit_action') }}" aria-label="{{ __('eys.users.edit_action') }}">
                                    <x-eys.icon name="edit" />
                                </a>
                                <form method="POST" action="{{ route('eys.equipment-brands.destroy', $equipmentBrand) }}" style="display: inline;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="button" class="ip-row-icon-btn" title="{{ __('eys.equipment_brand.delete_action') }}" aria-label="{{ __('eys.equipment_brand.delete_action') }}" onclick="eysConfirm(@js(__('eys.equipment_brand.delete_confirm')), this.closest('form'))">
                                        <x-eys.icon name="trash" />
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="ip-table-empty">{{ __('eys.equipment_brand.empty') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $equipmentBrands->onEachSide(1)->links('vendor.pagination.eys') }}
    </div>
</x-eys.app-layout>
