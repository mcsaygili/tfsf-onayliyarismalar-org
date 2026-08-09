<x-eys.app-layout :title="__('eys.uye.title')">
    <div class="ip-panel-stack">
        <div style="display: flex; align-items: center; justify-content: space-between; gap: 1rem;">
            <x-eys.breadcrumb :crumbs="[
                ['label' => __('eys.nav.dashboard'), 'url' => route('eys.dashboard')],
                ['label' => __('eys.module_names.Uye'), 'url' => route('eys.uye.dashboard')],
                ['label' => __('eys.uye.title')],
            ]" />
            <a href="{{ route('eys.uyeler.create') }}" class="ia-btn"><x-eys.icon name="plus" />{{ __('eys.common.add') }}</a>
        </div>

        <x-eys.filter-panel :action="route('eys.uyeler.index')" :reset-url="route('eys.uyeler.index')" :total="$members->total()">
            <x-eys.filter-search name="name" :label="__('eys.uye.filter_name')" :value="$filter['name']" />
            <div class="ip-filter-field">
                <label for="filter-status">{{ __('eys.uye.filter_status') }}</label>
                <select id="filter-status" name="status" class="ia-input" onchange="this.form.submit()">
                    <option value="">{{ __('eys.uye.filter_all_status') }}</option>
                    <option value="1" @selected($filter['status'] === '1')>{{ __('eys.uye.status_1') }}</option>
                    <option value="0" @selected($filter['status'] === '0')>{{ __('eys.uye.status_0') }}</option>
                    <option value="90" @selected($filter['status'] === '90')>{{ __('eys.uye.status_90') }}</option>
                </select>
            </div>
        </x-eys.filter-panel>

        <div class="ip-table-wrap">
            <table class="ip-table">
                <thead>
                    <tr>
                        <th>{{ __('eys.uye.column_name') }}</th>
                        <th>{{ __('eys.uye.column_username') }}</th>
                        <th>{{ __('eys.uye.column_email') }}</th>
                        <th>{{ __('eys.uye.column_uye_turu') }}</th>
                        <th>{{ __('eys.uye.column_status') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($members as $member)
                        <tr>
                            <td class="ip-cell-name">{{ trim(($member->first_name ?? '').' '.($member->last_name ?? '')) ?: '—' }}</td>
                            <td>{{ $member->username }}</td>
                            <td>{{ $member->email }}</td>
                            <td>{{ __('eys.uye.uye_turu_'.$member->uye_turu) }}</td>
                            <td>
                                @if ($member->status === 1)
                                    <span class="ip-badge is-active">{{ __('eys.uye.status_1') }}</span>
                                @elseif ($member->status === 90)
                                    <span class="ip-badge is-inactive">{{ __('eys.uye.status_90') }}</span>
                                @else
                                    <span class="ip-badge is-inactive">{{ __('eys.uye.status_0') }}</span>
                                @endif
                            </td>
                            <td style="text-align: right; white-space: nowrap;">
                                <a href="{{ route('eys.uyeler.edit', $member) }}" class="ip-row-icon-btn" title="{{ __('eys.users.edit_action') }}" aria-label="{{ __('eys.users.edit_action') }}">
                                    <x-eys.icon name="edit" />
                                </a>
                                <form method="POST" action="{{ route('eys.uyeler.destroy', $member) }}" style="display: inline;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="button" class="ip-row-icon-btn" title="{{ __('eys.uye.delete_action') }}" aria-label="{{ __('eys.uye.delete_action') }}" onclick="eysConfirm(@js(__('eys.uye.delete_confirm')), this.closest('form'))">
                                        <x-eys.icon name="trash" />
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="ip-table-empty">{{ __('eys.uye.empty') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $members->onEachSide(1)->links('vendor.pagination.eys') }}
    </div>
</x-eys.app-layout>
