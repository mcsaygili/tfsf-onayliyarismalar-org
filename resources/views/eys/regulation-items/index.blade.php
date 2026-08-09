<x-eys.app-layout :title="__('eys.regulation_item.title')">
    <div class="ip-panel-stack">
        <div style="display: flex; align-items: center; justify-content: space-between; gap: 1rem;">
            <x-eys.breadcrumb :crumbs="[
                ['label' => __('eys.nav.dashboard'), 'url' => route('eys.dashboard')],
                ['label' => __('eys.nav.section_competition_system')],
                ['label' => __('eys.regulation_item.title')],
            ]" />
            <a href="{{ route('eys.regulation-items.create') }}" class="ia-btn"><x-eys.icon name="plus" />{{ __('eys.common.add') }}</a>
        </div>

        <x-eys.filter-panel :action="route('eys.regulation-items.index')" :reset-url="route('eys.regulation-items.index')" :total="$items->total()">
            <x-eys.filter-search name="content" :label="__('eys.regulation_item.filter_content')" :value="$filter['content']" />
            <div class="ip-filter-field">
                <label for="filter-section">{{ __('eys.regulation_item.filter_section') }}</label>
                <select id="filter-section" name="regulation_section_id" class="ia-input" onchange="this.form.submit()">
                    <option value="">{{ __('eys.regulation_item.filter_all_sections') }}</option>
                    @foreach ($sectionOptions as $option)
                        <option value="{{ $option->id }}" @selected($filter['regulation_section_id'] === $option->id)>{{ $option->getTranslation()?->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="ip-filter-field">
                <label for="filter-status">{{ __('eys.regulation_item.filter_status') }}</label>
                <select id="filter-status" name="status" class="ia-input" onchange="this.form.submit()">
                    <option value="">{{ __('eys.regulation_item.filter_all_status') }}</option>
                    <option value="1" @selected($filter['status'] === '1')>{{ __('eys.regulation_item.status_active') }}</option>
                    <option value="0" @selected($filter['status'] === '0')>{{ __('eys.regulation_item.status_inactive') }}</option>
                </select>
            </div>
        </x-eys.filter-panel>

        <div class="ip-table-wrap">
            <table class="ip-table">
                <thead>
                    <tr>
                        <th>{{ __('eys.regulation_item.column_section') }}</th>
                        <th>{{ __('eys.regulation_item.column_content') }}</th>
                        <th>{{ __('eys.regulation_item.column_order') }}</th>
                        <th>{{ __('eys.regulation_item.column_status') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($items as $item)
                        <tr>
                            <td>{{ $item->section?->getTranslation()?->name ?? '—' }}</td>
                            <td class="ip-cell-name">{{ \Illuminate\Support\Str::limit($item->getTranslation()?->content ?? '—', 80) }}</td>
                            <td>{{ $item->sort_order }}</td>
                            <td>
                                @if ($item->status)
                                    <span class="ip-badge is-active">{{ __('eys.regulation_item.status_active') }}</span>
                                @else
                                    <span class="ip-badge is-inactive">{{ __('eys.regulation_item.status_inactive') }}</span>
                                @endif
                            </td>
                            <td style="text-align: right; white-space: nowrap;">
                                <a href="{{ route('eys.regulation-items.edit', $item) }}" class="ip-row-icon-btn" title="{{ __('eys.users.edit_action') }}" aria-label="{{ __('eys.users.edit_action') }}">
                                    <x-eys.icon name="edit" />
                                </a>
                                <form method="POST" action="{{ route('eys.regulation-items.destroy', $item) }}" style="display: inline;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="button" class="ip-row-icon-btn" title="{{ __('eys.regulation_item.delete_action') }}" aria-label="{{ __('eys.regulation_item.delete_action') }}" onclick="eysConfirm(@js(__('eys.regulation_item.delete_confirm')), this.closest('form'))">
                                        <x-eys.icon name="trash" />
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="ip-table-empty">{{ __('eys.regulation_item.empty') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $items->onEachSide(1)->links('vendor.pagination.eys') }}
    </div>
</x-eys.app-layout>
