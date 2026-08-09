<x-eys.app-layout :title="__('eys.regulation_section.title')">
    <div class="ip-panel-stack">
        <div style="display: flex; align-items: center; justify-content: space-between; gap: 1rem;">
            <x-eys.breadcrumb :crumbs="[
                ['label' => __('eys.nav.dashboard'), 'url' => route('eys.dashboard')],
                ['label' => __('eys.nav.section_competition_system')],
                ['label' => __('eys.regulation_section.title')],
            ]" />
            <a href="{{ route('eys.regulation-sections.create') }}" class="ia-btn"><x-eys.icon name="plus" />{{ __('eys.common.add') }}</a>
        </div>

        <x-eys.filter-panel :action="route('eys.regulation-sections.index')" :reset-url="route('eys.regulation-sections.index')" :total="$sections->total()">
            <x-eys.filter-search name="name" :label="__('eys.regulation_section.filter_name')" :value="$filter['name']" />
            <div class="ip-filter-field">
                <label for="filter-status">{{ __('eys.regulation_section.filter_status') }}</label>
                <select id="filter-status" name="status" class="ia-input" onchange="this.form.submit()">
                    <option value="">{{ __('eys.regulation_section.filter_all_status') }}</option>
                    <option value="1" @selected($filter['status'] === '1')>{{ __('eys.regulation_section.status_active') }}</option>
                    <option value="0" @selected($filter['status'] === '0')>{{ __('eys.regulation_section.status_inactive') }}</option>
                </select>
            </div>
        </x-eys.filter-panel>

        <div class="ip-table-wrap">
            <table class="ip-table">
                <thead>
                    <tr>
                        <th>{{ __('eys.regulation_section.column_name') }}</th>
                        <th>{{ __('eys.regulation_section.column_order') }}</th>
                        <th>{{ __('eys.regulation_section.column_item_count') }}</th>
                        <th>{{ __('eys.regulation_section.column_status') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($sections as $section)
                        <tr>
                            <td class="ip-cell-name">{{ $section->getTranslation()?->name ?? '—' }}</td>
                            <td>{{ $section->sort_order }}</td>
                            <td>{{ $section->items_count }}</td>
                            <td>
                                @if ($section->status)
                                    <span class="ip-badge is-active">{{ __('eys.regulation_section.status_active') }}</span>
                                @else
                                    <span class="ip-badge is-inactive">{{ __('eys.regulation_section.status_inactive') }}</span>
                                @endif
                            </td>
                            <td style="text-align: right; white-space: nowrap;">
                                <a href="{{ route('eys.regulation-sections.edit', $section) }}" class="ip-row-icon-btn" title="{{ __('eys.users.edit_action') }}" aria-label="{{ __('eys.users.edit_action') }}">
                                    <x-eys.icon name="edit" />
                                </a>
                                <form method="POST" action="{{ route('eys.regulation-sections.destroy', $section) }}" style="display: inline;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="button" class="ip-row-icon-btn" title="{{ __('eys.regulation_section.delete_action') }}" aria-label="{{ __('eys.regulation_section.delete_action') }}" onclick="eysConfirm(@js(__('eys.regulation_section.delete_confirm')), this.closest('form'))">
                                        <x-eys.icon name="trash" />
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="ip-table-empty">{{ __('eys.regulation_section.empty') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $sections->onEachSide(1)->links('vendor.pagination.eys') }}
    </div>
</x-eys.app-layout>
