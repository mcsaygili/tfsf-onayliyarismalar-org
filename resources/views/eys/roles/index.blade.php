<x-eys.app-layout :title="__('eys.role.title')">
    <div class="ip-panel-stack">
        <div style="display: flex; align-items: center; justify-content: space-between; gap: 1rem;">
            <x-eys.breadcrumb :crumbs="[
                ['label' => __('eys.nav.dashboard'), 'url' => route('eys.dashboard')],
                ['label' => __('eys.role.title')],
            ]" />
            <a href="{{ route('eys.roles.create') }}" class="ia-btn"><x-eys.icon name="plus" />{{ __('eys.common.add') }}</a>
        </div>

        <x-eys.filter-panel :action="route('eys.roles.index')" :reset-url="route('eys.roles.index')" :total="$roles->total()">
            <div class="ip-filter-field">
                <label for="filter-module">{{ __('eys.role.filter_module') }}</label>
                <select id="filter-module" name="module" class="ia-input" onchange="this.form.submit()">
                    <option value="">{{ __('eys.role.filter_all_modules') }}</option>
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
                        <th>{{ __('eys.role.column_name') }}</th>
                        <th>{{ __('eys.role.column_module') }}</th>
                        <th>{{ __('eys.role.column_permissions') }}</th>
                        <th>{{ __('eys.role.column_users') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($roles as $role)
                        <tr>
                            <td class="ip-cell-name">{{ $role->label ?: $role->name }}</td>
                            <td>{{ $moduleByValue[$role->team_id] ?? '—' }}</td>
                            <td>{{ $role->permissions->count() }}</td>
                            <td>{{ $userCounts[$role->id] ?? 0 }}</td>
                            <td style="text-align: right; white-space: nowrap;">
                                <a href="{{ route('eys.roles.edit', $role) }}" class="ip-row-icon-btn" title="{{ __('eys.users.edit_action') }}" aria-label="{{ __('eys.users.edit_action') }}">
                                    <x-eys.icon name="edit" />
                                </a>
                                <form method="POST" action="{{ route('eys.roles.destroy', $role) }}" style="display: inline;" onsubmit="return confirm(@js(__('eys.role.delete_confirm')));">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="ip-row-icon-btn" title="{{ __('eys.role.delete_action') }}" aria-label="{{ __('eys.role.delete_action') }}">
                                        <x-eys.icon name="trash" />
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="ip-table-empty">{{ __('eys.role.empty') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $roles->onEachSide(1)->links('vendor.pagination.eys') }}
    </div>
</x-eys.app-layout>
