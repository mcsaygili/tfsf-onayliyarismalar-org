<x-eys.app-layout :title="__('eys.temsilci.title')">
    <div class="ip-panel-stack">
        <div style="display: flex; align-items: center; justify-content: space-between; gap: 1rem;">
            <x-eys.breadcrumb :crumbs="[
                ['label' => __('eys.nav.dashboard'), 'url' => route('eys.dashboard')],
                ['label' => __('eys.module_names.Temsilci'), 'url' => route('eys.temsilci.dashboard')],
                ['label' => __('eys.temsilci.title')],
            ]" />
            <a href="{{ route('eys.temsilciler.create') }}" class="ia-btn"><x-eys.icon name="plus" />{{ __('eys.common.add') }}</a>
        </div>

        <x-eys.filter-panel :action="route('eys.temsilciler.index')" :reset-url="route('eys.temsilciler.index')" :total="$temsilciler->total()">
            <x-eys.filter-search name="name" :label="__('eys.temsilci.filter_name')" :value="$filter['name']" />
            <div class="ip-filter-field">
                <label for="filter-status">{{ __('eys.temsilci.filter_status') }}</label>
                <select id="filter-status" name="status" class="ia-input" onchange="this.form.submit()">
                    <option value="">{{ __('eys.temsilci.filter_all_status') }}</option>
                    <option value="1" @selected($filter['status'] === '1')>{{ __('eys.temsilci.status_active') }}</option>
                    <option value="0" @selected($filter['status'] === '0')>{{ __('eys.temsilci.status_inactive') }}</option>
                </select>
            </div>
        </x-eys.filter-panel>

        <div class="ip-table-wrap">
            <table class="ip-table">
                <thead>
                    <tr>
                        <th>{{ __('eys.temsilci.column_name') }}</th>
                        <th>{{ __('eys.temsilci.column_email') }}</th>
                        <th>{{ __('eys.temsilci.column_education_level') }}</th>
                        <th>{{ __('eys.temsilci.column_status') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($temsilciler as $temsilci)
                        <tr>
                            <td class="ip-cell-name">{{ trim(($temsilci->first_name ?? '').' '.($temsilci->last_name ?? '')) ?: '—' }}</td>
                            <td>{{ $temsilci->email }}</td>
                            <td>{{ $temsilci->educationLevel?->getTranslation()?->name ?? '—' }}</td>
                            <td>
                                @if ($temsilci->status)
                                    <span class="ip-badge is-active">{{ __('eys.temsilci.status_active') }}</span>
                                @else
                                    <span class="ip-badge is-inactive">{{ __('eys.temsilci.status_inactive') }}</span>
                                @endif
                            </td>
                            <td style="text-align: right; white-space: nowrap;">
                                <a href="{{ route('eys.temsilciler.edit', $temsilci) }}" class="ip-row-icon-btn" title="{{ __('eys.users.edit_action') }}" aria-label="{{ __('eys.users.edit_action') }}">
                                    <x-eys.icon name="edit" />
                                </a>
                                <form method="POST" action="{{ route('eys.temsilciler.destroy', $temsilci) }}" style="display: inline;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="button" class="ip-row-icon-btn" title="{{ __('eys.temsilci.delete_action') }}" aria-label="{{ __('eys.temsilci.delete_action') }}" onclick="eysConfirm(@js(__('eys.temsilci.delete_confirm')), this.closest('form'))">
                                        <x-eys.icon name="trash" />
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="ip-table-empty">{{ __('eys.temsilci.empty') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $temsilciler->onEachSide(1)->links('vendor.pagination.eys') }}
    </div>
</x-eys.app-layout>
