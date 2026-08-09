<x-eys.app-layout :title="__('eys.city.title')">
    <div class="ip-panel-stack">
        <div style="display: flex; align-items: center; justify-content: space-between; gap: 1rem;">
            <x-eys.breadcrumb :crumbs="[
                ['label' => __('eys.nav.dashboard'), 'url' => route('eys.dashboard')],
                ['label' => __('eys.nav.reference_data')],
                ['label' => __('eys.city.title')],
            ]" />
            <a href="{{ route('eys.cities.create') }}" class="ia-btn"><x-eys.icon name="plus" />{{ __('eys.common.add') }}</a>
        </div>

        <x-eys.filter-panel :action="route('eys.cities.index')" :reset-url="route('eys.cities.index')" :total="$cities->total()">
            <x-eys.filter-search name="name" :label="__('eys.city.filter_name')" :value="$filter['name']" />
            <div class="ip-filter-field">
                <label for="filter-country">{{ __('eys.city.filter_country') }}</label>
                <select id="filter-country" name="country_id" class="ia-input" onchange="this.form.submit()">
                    <option value="">{{ __('eys.city.filter_all_countries') }}</option>
                    @foreach ($countryOptions as $id => $label)
                        <option value="{{ $id }}" @selected($filter['country_id'] === $id)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="ip-filter-field">
                <label for="filter-status">{{ __('eys.city.filter_status') }}</label>
                <select id="filter-status" name="status" class="ia-input" onchange="this.form.submit()">
                    <option value="">{{ __('eys.city.filter_all_status') }}</option>
                    <option value="1" @selected($filter['status'] === '1')>{{ __('eys.city.status_active') }}</option>
                    <option value="0" @selected($filter['status'] === '0')>{{ __('eys.city.status_inactive') }}</option>
                </select>
            </div>
        </x-eys.filter-panel>

        <div class="ip-table-wrap">
            <table class="ip-table">
                <thead>
                    <tr>
                        <th>{{ __('eys.city.column_name') }}</th>
                        <th>{{ __('eys.city.column_country') }}</th>
                        <th>{{ __('eys.city.column_status') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($cities as $city)
                        <tr>
                            <td class="ip-cell-name">{{ $city->getTranslation()?->official_name ?? '—' }}</td>
                            <td>{{ $city->country?->getTranslation()?->short_name ?? $city->country?->getTranslation()?->official_name ?? '—' }}</td>
                            <td>
                                @if ($city->status)
                                    <span class="ip-badge is-active">{{ __('eys.city.status_active') }}</span>
                                @else
                                    <span class="ip-badge is-inactive">{{ __('eys.city.status_inactive') }}</span>
                                @endif
                            </td>
                            <td style="text-align: right; white-space: nowrap;">
                                <a href="{{ route('eys.cities.edit', $city) }}" class="ip-row-icon-btn" title="{{ __('eys.users.edit_action') }}" aria-label="{{ __('eys.users.edit_action') }}">
                                    <x-eys.icon name="edit" />
                                </a>
                                <form method="POST" action="{{ route('eys.cities.destroy', $city) }}" style="display: inline;" onsubmit="return confirm(@js(__('eys.city.delete_confirm')));">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="ip-row-icon-btn" title="{{ __('eys.city.delete_action') }}" aria-label="{{ __('eys.city.delete_action') }}">
                                        <x-eys.icon name="trash" />
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="ip-table-empty">{{ __('eys.city.empty') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $cities->onEachSide(1)->links('vendor.pagination.eys') }}
    </div>
</x-eys.app-layout>
