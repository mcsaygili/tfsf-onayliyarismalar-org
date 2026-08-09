<x-eys.app-layout :title="__('eys.permission.title')">
    <div class="ip-panel-stack">
        <div style="display: flex; align-items: center; justify-content: space-between; gap: 1rem;">
            <x-eys.breadcrumb :crumbs="[
                ['label' => __('eys.nav.dashboard'), 'url' => route('eys.dashboard')],
                ['label' => __('eys.permission.title')],
            ]" />
            <a href="{{ route('eys.permissions.create') }}" class="ia-btn"><x-eys.icon name="plus" />{{ __('eys.common.add') }}</a>
        </div>

        <x-eys.filter-panel :action="route('eys.permissions.index')" :reset-url="route('eys.permissions.index')" :total="$permissions->total()">
            <div class="ip-filter-field">
                <label for="filter-module">{{ __('eys.permission.filter_module') }}</label>
                <select id="filter-module" name="module" class="ia-input" onchange="this.form.submit()">
                    <option value="">{{ __('eys.permission.filter_all_modules') }}</option>
                    @foreach ($moduleLabels as $value => $label)
                        <option value="{{ $value }}" @selected($filterModule === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <x-eys.filter-search name="q" :label="__('eys.common.filter_search')" :value="$filterSearch" />
        </x-eys.filter-panel>

        <div class="ip-table-wrap">
            <table class="ip-table">
                <thead>
                    <tr>
                        <th>{{ __('eys.permission.column_name') }}</th>
                        <th>{{ __('eys.permission.column_module') }}</th>
                        <th>{{ __('eys.permission.column_group') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($permissions as $permission)
                        <tr>
                            <td class="ip-cell-name" style="font-family: monospace;">{{ $permission->name }}</td>
                            <td>{{ $moduleLabels[$permission->module] ?? $permission->module }}</td>
                            <td>{{ $permission->group ?: '—' }}</td>
                            <td style="text-align: right; white-space: nowrap;">
                                <a href="{{ route('eys.permissions.edit', $permission) }}" class="ip-row-icon-btn" title="{{ __('eys.users.edit_action') }}" aria-label="{{ __('eys.users.edit_action') }}">
                                    <x-eys.icon name="edit" />
                                </a>
                                <form method="POST" action="{{ route('eys.permissions.destroy', $permission) }}" style="display: inline;" onsubmit="return confirm(@js(__('eys.permission.delete_confirm')));">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="ip-row-icon-btn" title="{{ __('eys.permission.delete_action') }}" aria-label="{{ __('eys.permission.delete_action') }}">
                                        <x-eys.icon name="trash" />
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="ip-table-empty">{{ __('eys.permission.empty') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $permissions->onEachSide(1)->links('vendor.pagination.eys') }}
    </div>
</x-eys.app-layout>
