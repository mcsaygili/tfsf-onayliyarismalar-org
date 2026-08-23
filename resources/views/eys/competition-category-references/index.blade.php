@php
    $title = __("eys.$translation.title");
    $filterName = __("eys.$translation.filter_name");
    $deleteConfirm = __("eys.$translation.delete_confirm");
@endphp
<x-eys.app-layout :title="$title">
    <div class="ip-panel-stack">
        <div class="ip-page-actions">
            <x-eys.breadcrumb :crumbs="[
                ['label' => __('eys.nav.dashboard'), 'url' => route('eys.dashboard')],
                ['label' => __('eys.nav.section_competition_system')],
                ['label' => __('eys.nav.reference_data')],
                ['label' => $title],
            ]" />
            <a href="{{ route($route.'.create') }}" class="ia-btn"><x-eys.icon name="plus" />{{ __('eys.common.add') }}</a>
        </div>
        <p class="ip-section-hint">{{ __("eys.$translation.list_hint") }}</p>
        <x-eys.filter-panel :action="route($route.'.index')" :reset-url="route($route.'.index')" :total="$references->total()">
            <x-eys.filter-search name="name" :label="$filterName" :value="$filter['name']" />
            <div class="ip-filter-field">
                <label for="filter-status">{{ __("eys.$translation.status") }}</label>
                <select id="filter-status" name="status" class="ia-input" onchange="this.form.submit()">
                    <option value="">{{ __('eys.common.all') }}</option>
                    <option value="1" @selected($filter['status'] === '1')>{{ __('eys.common.active') }}</option>
                    <option value="0" @selected($filter['status'] === '0')>{{ __('eys.common.inactive') }}</option>
                </select>
            </div>
        </x-eys.filter-panel>
        <div class="ip-table-wrap">
            <table class="ip-table">
                <thead><tr><th>{{ __("eys.$translation.name") }}</th><th>{{ __("eys.$translation.code") }}</th><th>{{ __("eys.$translation.sort_order") }}</th><th>{{ __("eys.$translation.status") }}</th><th></th></tr></thead>
                <tbody>
                    @forelse ($references as $reference)
                        <tr>
                            <td class="ip-cell-name">{{ $reference->name }}</td><td><code>{{ $reference->code }}</code></td><td>{{ $reference->sort_order }}</td>
                            <td><span class="ip-badge {{ $reference->status ? 'is-active' : 'is-inactive' }}">{{ $reference->status ? __('eys.common.active') : __('eys.common.inactive') }}</span></td>
                            <td class="ip-table-actions">
                                <a href="{{ route($route.'.edit', $reference) }}" class="ip-row-icon-btn"><x-eys.icon name="edit" /></a>
                                <form method="POST" action="{{ route($route.'.destroy', $reference) }}" class="ip-inline-form">
                                    @csrf @method('DELETE')
                                    <button type="button" class="ip-row-icon-btn" onclick="eysConfirm(@js($deleteConfirm), this.closest('form'))"><x-eys.icon name="trash" /></button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="ip-table-empty">{{ __("eys.$translation.empty") }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $references->onEachSide(1)->links('vendor.pagination.eys') }}
    </div>
</x-eys.app-layout>
