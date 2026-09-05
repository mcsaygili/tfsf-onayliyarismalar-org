<x-eys.app-layout :title="__('eys.nav.users')">
    <div class="ip-panel-stack">
        <div style="display: flex; align-items: center; justify-content: space-between; gap: 1rem;">
            <x-eys.breadcrumb :crumbs="[
                ['label' => __('eys.nav.dashboard'), 'url' => route('eys.dashboard')],
                ['label' => __('eys.users.list_title')],
            ]" />
            @can('create', \App\Models\EysUser::class)
<a href="{{ route('eys.users.create') }}" class="ia-btn"><x-eys.icon name="plus" />{{ __('eys.common.add') }}</a>
@endcan
        </div>

        <x-eys.filter-panel :action="route('eys.users.index')" :reset-url="route('eys.users.index')" :total="$users->total()">
            <x-eys.filter-search name="q" :label="__('eys.users.filter_search')" :value="$filter['q']" />
            <div class="ip-filter-field">
                <label for="filter-status">{{ __('eys.users.filter_status') }}</label>
                <select id="filter-status" name="status" class="ia-input" onchange="this.form.submit()">
                    <option value="">{{ __('eys.users.filter_all_status') }}</option>
                    <option value="1" @selected($filter['status'] === '1')>{{ __('eys.users.status_active') }}</option>
                    <option value="0" @selected($filter['status'] === '0')>{{ __('eys.users.status_inactive') }}</option>
                </select>
            </div>
        </x-eys.filter-panel>

        <div class="ip-table-wrap">
            <table class="ip-table">
                <thead>
                    <tr>
                        <th>{{ __('eys.users.column_name') }}</th>
                        <th>{{ __('eys.users.column_email') }}</th>
                        <th>{{ __('eys.users.column_phone') }}</th>
                        <th>{{ __('eys.users.column_status') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($users as $user)
                        <tr>
                            <td class="ip-cell-name">{{ trim($user->first_name.' '.$user->last_name) ?: '—' }}</td>
                            <td>{{ $user->email }}</td>
                            <td>{{ $user->phone ?: '—' }}</td>
                            <td>
                                @if ($user->status)
                                    <span class="ip-badge is-active">{{ __('eys.users.status_active') }}</span>
                                @else
                                    <span class="ip-badge is-inactive">{{ __('eys.users.status_inactive') }}</span>
                                @endif
                            </td>
                            <td style="text-align: right; white-space: nowrap;">
                                @can('eys.roles.manage')
<a href="{{ route('eys.users.roles.edit', $user) }}" class="ip-row-icon-btn" title="{{ __('eys.users.manage_roles') }}" aria-label="{{ __('eys.users.manage_roles') }}">
                                    <x-eys.icon name="role" />
                                </a>
@endcan
                                @can('update', $user)
<a href="{{ route('eys.users.edit', $user) }}" class="ip-row-icon-btn" title="{{ __('eys.users.edit_action') }}" aria-label="{{ __('eys.users.edit_action') }}">
                                    <x-eys.icon name="edit" />
                                </a>
@endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="ip-table-empty">{{ __('eys.users.empty') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $users->onEachSide(1)->links('vendor.pagination.eys') }}
    </div>
</x-eys.app-layout>
