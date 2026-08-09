<x-eys.app-layout :title="__('eys.juri.title')">
    <div class="ip-panel-stack">
        <div style="display: flex; align-items: center; justify-content: space-between; gap: 1rem;">
            <x-eys.breadcrumb :crumbs="[
                ['label' => __('eys.nav.dashboard'), 'url' => route('eys.dashboard')],
                ['label' => __('eys.module_names.Juri'), 'url' => route('eys.juri.dashboard')],
                ['label' => __('eys.juri.title')],
            ]" />
            <a href="{{ route('eys.juriler.create') }}" class="ia-btn"><x-eys.icon name="plus" />{{ __('eys.common.add') }}</a>
        </div>

        <x-eys.filter-panel :action="route('eys.juriler.index')" :reset-url="route('eys.juriler.index')" :total="$juriler->total()">
            <x-eys.filter-search name="name" :label="__('eys.juri.filter_name')" :value="$filter['name']" />
            <div class="ip-filter-field">
                <label for="filter-status">{{ __('eys.juri.filter_status') }}</label>
                <select id="filter-status" name="status" class="ia-input" onchange="this.form.submit()">
                    <option value="">{{ __('eys.juri.filter_all_status') }}</option>
                    <option value="1" @selected($filter['status'] === '1')>{{ __('eys.juri.status_active') }}</option>
                    <option value="0" @selected($filter['status'] === '0')>{{ __('eys.juri.status_inactive') }}</option>
                </select>
            </div>
        </x-eys.filter-panel>

        <div class="ip-table-wrap">
            <table class="ip-table">
                <thead>
                    <tr>
                        <th>{{ __('eys.juri.column_name') }}</th>
                        <th>{{ __('eys.juri.column_email') }}</th>
                        <th>{{ __('eys.juri.column_education_level') }}</th>
                        <th>{{ __('eys.juri.column_status') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($juriler as $juri)
                        <tr>
                            <td class="ip-cell-name">{{ trim(($juri->first_name ?? '').' '.($juri->last_name ?? '')) ?: '—' }}</td>
                            <td>{{ $juri->email }}</td>
                            <td>{{ $juri->educationLevel?->getTranslation()?->name ?? '—' }}</td>
                            <td>
                                @if ($juri->status)
                                    <span class="ip-badge is-active">{{ __('eys.juri.status_active') }}</span>
                                @else
                                    <span class="ip-badge is-inactive">{{ __('eys.juri.status_inactive') }}</span>
                                @endif
                            </td>
                            <td style="text-align: right; white-space: nowrap;">
                                <a href="{{ route('eys.juriler.edit', $juri) }}" class="ip-row-icon-btn" title="{{ __('eys.users.edit_action') }}" aria-label="{{ __('eys.users.edit_action') }}">
                                    <x-eys.icon name="edit" />
                                </a>
                                <form method="POST" action="{{ route('eys.juriler.destroy', $juri) }}" style="display: inline;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="button" class="ip-row-icon-btn" title="{{ __('eys.juri.delete_action') }}" aria-label="{{ __('eys.juri.delete_action') }}" onclick="eysConfirm(@js(__('eys.juri.delete_confirm')), this.closest('form'))">
                                        <x-eys.icon name="trash" />
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="ip-table-empty">{{ __('eys.juri.empty') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $juriler->onEachSide(1)->links('vendor.pagination.eys') }}
    </div>
</x-eys.app-layout>
