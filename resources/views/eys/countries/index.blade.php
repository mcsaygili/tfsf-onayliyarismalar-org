<x-eys.app-layout :title="__('eys.country.title')">
    <div class="ip-panel-stack">
        <div style="display: flex; align-items: center; justify-content: space-between; gap: 1rem;">
            <x-eys.breadcrumb :crumbs="[
                ['label' => __('eys.nav.dashboard'), 'url' => route('eys.dashboard')],
                ['label' => __('eys.nav.reference_data')],
                ['label' => __('eys.country.title')],
            ]" />
            <a href="{{ route('eys.countries.create') }}" class="ia-btn"><x-eys.icon name="plus" />{{ __('eys.common.add') }}</a>
        </div>

        <x-eys.filter-panel :action="route('eys.countries.index')" :reset-url="route('eys.countries.index')" :total="$countries->total()">
            <x-eys.filter-search name="name" :label="__('eys.country.filter_name')" :value="$filter['name']" />
            <div class="ip-filter-field">
                <label for="filter-status">{{ __('eys.country.filter_status') }}</label>
                <select id="filter-status" name="status" class="ia-input" onchange="this.form.submit()">
                    <option value="">{{ __('eys.country.filter_all_status') }}</option>
                    <option value="1" @selected($filter['status'] === '1')>{{ __('eys.country.status_active') }}</option>
                    <option value="0" @selected($filter['status'] === '0')>{{ __('eys.country.status_inactive') }}</option>
                </select>
            </div>
        </x-eys.filter-panel>

        <div class="ip-table-wrap">
            <table class="ip-table">
                <thead>
                    <tr>
                        <th>{{ __('eys.country.column_name') }}</th>
                        <th>{{ __('eys.country.column_iso') }}</th>
                        <th>{{ __('eys.country.column_cities') }}</th>
                        <th>{{ __('eys.country.column_status') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($countries as $country)
                        <tr>
                            <td class="ip-cell-name">{{ $country->getTranslation()?->official_name ?? '—' }}</td>
                            <td>{{ $country->iso_alpha2 ?: '—' }} / {{ $country->iso_alpha3 ?: '—' }}</td>
                            <td>{{ $country->cities_count }}</td>
                            <td>
                                @if ($country->status)
                                    <span class="ip-badge is-active">{{ __('eys.country.status_active') }}</span>
                                @else
                                    <span class="ip-badge is-inactive">{{ __('eys.country.status_inactive') }}</span>
                                @endif
                            </td>
                            <td style="text-align: right; white-space: nowrap;">
                                <a href="{{ route('eys.countries.edit', $country) }}" class="ip-row-icon-btn" title="{{ __('eys.users.edit_action') }}" aria-label="{{ __('eys.users.edit_action') }}">
                                    <x-eys.icon name="edit" />
                                </a>
                                <form method="POST" action="{{ route('eys.countries.destroy', $country) }}" style="display: inline;" onsubmit="return confirm(@js(__('eys.country.delete_confirm')));">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="ip-row-icon-btn" title="{{ __('eys.country.delete_action') }}" aria-label="{{ __('eys.country.delete_action') }}">
                                        <x-eys.icon name="trash" />
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="ip-table-empty">{{ __('eys.country.empty') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $countries->onEachSide(1)->links('vendor.pagination.eys') }}
    </div>
</x-eys.app-layout>
