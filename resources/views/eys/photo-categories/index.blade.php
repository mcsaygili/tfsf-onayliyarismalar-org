<x-eys.app-layout :title="__('eys.photo_category.title')">
    <div class="ip-panel-stack">
        <div style="display: flex; align-items: center; justify-content: space-between; gap: 1rem;">
            <x-eys.breadcrumb :crumbs="[
                ['label' => __('eys.nav.dashboard'), 'url' => route('eys.dashboard')],
                ['label' => __('eys.nav.reference_data')],
                ['label' => __('eys.photo_category.title')],
            ]" />
            <a href="{{ route('eys.photo-categories.create') }}" class="ia-btn"><x-eys.icon name="plus" />{{ __('eys.common.add') }}</a>
        </div>

        <x-eys.filter-panel :action="route('eys.photo-categories.index')" :reset-url="route('eys.photo-categories.index')" :total="$photoCategories->total()">
            <x-eys.filter-search name="name" :label="__('eys.photo_category.filter_name')" :value="$filter['name']" />
            <div class="ip-filter-field">
                <label for="filter-status">{{ __('eys.photo_category.filter_status') }}</label>
                <select id="filter-status" name="status" class="ia-input" onchange="this.form.submit()">
                    <option value="">{{ __('eys.photo_category.filter_all_status') }}</option>
                    <option value="1" @selected($filter['status'] === '1')>{{ __('eys.photo_category.status_active') }}</option>
                    <option value="0" @selected($filter['status'] === '0')>{{ __('eys.photo_category.status_inactive') }}</option>
                </select>
            </div>
        </x-eys.filter-panel>

        <div class="ip-table-wrap">
            <table class="ip-table">
                <thead>
                    <tr>
                        <th>{{ __('eys.photo_category.column_name') }}</th>
                        <th>{{ __('eys.photo_category.column_order') }}</th>
                        <th>{{ __('eys.photo_category.column_status') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($photoCategories as $photoCategory)
                        <tr>
                            <td class="ip-cell-name">{{ $photoCategory->getTranslation()?->name ?? '—' }}</td>
                            <td>{{ $photoCategory->sort_order }}</td>
                            <td>
                                @if ($photoCategory->status)
                                    <span class="ip-badge is-active">{{ __('eys.photo_category.status_active') }}</span>
                                @else
                                    <span class="ip-badge is-inactive">{{ __('eys.photo_category.status_inactive') }}</span>
                                @endif
                            </td>
                            <td style="text-align: right; white-space: nowrap;">
                                <a href="{{ route('eys.photo-categories.edit', $photoCategory) }}" class="ip-row-icon-btn" title="{{ __('eys.users.edit_action') }}" aria-label="{{ __('eys.users.edit_action') }}">
                                    <x-eys.icon name="edit" />
                                </a>
                                <form method="POST" action="{{ route('eys.photo-categories.destroy', $photoCategory) }}" style="display: inline;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="button" class="ip-row-icon-btn" title="{{ __('eys.photo_category.delete_action') }}" aria-label="{{ __('eys.photo_category.delete_action') }}" onclick="eysConfirm(@js(__('eys.photo_category.delete_confirm')), this.closest('form'))">
                                        <x-eys.icon name="trash" />
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="ip-table-empty">{{ __('eys.photo_category.empty') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $photoCategories->onEachSide(1)->links('vendor.pagination.eys') }}
    </div>
</x-eys.app-layout>
