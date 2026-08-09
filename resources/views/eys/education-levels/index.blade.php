<x-eys.app-layout :title="__('eys.education_level.title')">
    <div class="ip-panel-stack">
        <div style="display: flex; align-items: center; justify-content: space-between; gap: 1rem;">
            <x-eys.breadcrumb :crumbs="[
                ['label' => __('eys.nav.dashboard'), 'url' => route('eys.dashboard')],
                ['label' => __('eys.nav.reference_data')],
                ['label' => __('eys.education_level.title')],
            ]" />
            <a href="{{ route('eys.education-levels.create') }}" class="ia-btn"><x-eys.icon name="plus" />{{ __('eys.common.add') }}</a>
        </div>

        <x-eys.filter-panel :action="route('eys.education-levels.index')" :reset-url="route('eys.education-levels.index')" :total="$educationLevels->total()">
            <x-eys.filter-search name="name" :label="__('eys.education_level.filter_name')" :value="$filter['name']" />
            <div class="ip-filter-field">
                <label for="filter-status">{{ __('eys.education_level.filter_status') }}</label>
                <select id="filter-status" name="status" class="ia-input" onchange="this.form.submit()">
                    <option value="">{{ __('eys.education_level.filter_all_status') }}</option>
                    <option value="1" @selected($filter['status'] === '1')>{{ __('eys.education_level.status_active') }}</option>
                    <option value="0" @selected($filter['status'] === '0')>{{ __('eys.education_level.status_inactive') }}</option>
                </select>
            </div>
        </x-eys.filter-panel>

        <div class="ip-table-wrap">
            <table class="ip-table">
                <thead>
                    <tr>
                        <th>{{ __('eys.education_level.column_name') }}</th>
                        <th>{{ __('eys.education_level.column_order') }}</th>
                        <th>{{ __('eys.education_level.column_status') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($educationLevels as $educationLevel)
                        <tr>
                            <td class="ip-cell-name">{{ $educationLevel->getTranslation()?->name ?? '—' }}</td>
                            <td>{{ $educationLevel->sort_order }}</td>
                            <td>
                                @if ($educationLevel->status)
                                    <span class="ip-badge is-active">{{ __('eys.education_level.status_active') }}</span>
                                @else
                                    <span class="ip-badge is-inactive">{{ __('eys.education_level.status_inactive') }}</span>
                                @endif
                            </td>
                            <td style="text-align: right; white-space: nowrap;">
                                <a href="{{ route('eys.education-levels.edit', $educationLevel) }}" class="ip-row-icon-btn" title="{{ __('eys.users.edit_action') }}" aria-label="{{ __('eys.users.edit_action') }}">
                                    <x-eys.icon name="edit" />
                                </a>
                                <form method="POST" action="{{ route('eys.education-levels.destroy', $educationLevel) }}" style="display: inline;" onsubmit="return confirm(@js(__('eys.education_level.delete_confirm')));">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="ip-row-icon-btn" title="{{ __('eys.education_level.delete_action') }}" aria-label="{{ __('eys.education_level.delete_action') }}">
                                        <x-eys.icon name="trash" />
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="ip-table-empty">{{ __('eys.education_level.empty') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $educationLevels->onEachSide(1)->links('vendor.pagination.eys') }}
    </div>
</x-eys.app-layout>
