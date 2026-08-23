<x-eys.app-layout :title="__('eys.competition_type.title')">
    <div class="ip-panel-stack">
        <div class="ip-page-actions">
            <x-eys.breadcrumb :crumbs="[
                ['label' => __('eys.nav.dashboard'), 'url' => route('eys.dashboard')],
                ['label' => __('eys.nav.section_competition_system')],
                ['label' => __('eys.nav.reference_data')],
                ['label' => __('eys.competition_type.title')],
            ]" />
            <a href="{{ route('eys.competition-types.create') }}" class="ia-btn"><x-eys.icon name="plus" />{{ __('eys.common.add') }}</a>
        </div>

        <p class="ip-section-hint">{{ __('eys.competition_type.list_hint') }}</p>

        <x-eys.filter-panel :action="route('eys.competition-types.index')" :reset-url="route('eys.competition-types.index')" :total="$competitionTypes->total()">
            <x-eys.filter-search name="name" :label="__('eys.competition_type.filter_name')" :value="$filter['name']" />
            <div class="ip-filter-field">
                <label for="filter-status">{{ __('eys.competition_type.filter_status') }}</label>
                <select id="filter-status" name="status" class="ia-input" onchange="this.form.submit()">
                    <option value="">{{ __('eys.competition_type.filter_all_status') }}</option>
                    <option value="1" @selected($filter['status'] === '1')>{{ __('eys.competition_type.status_active') }}</option>
                    <option value="0" @selected($filter['status'] === '0')>{{ __('eys.competition_type.status_inactive') }}</option>
                </select>
            </div>
        </x-eys.filter-panel>

        <div class="ip-table-wrap">
            <table class="ip-table">
                <thead>
                    <tr>
                        <th>{{ __('eys.competition_type.column_name') }}</th>
                        <th>{{ __('eys.competition_type.column_code') }}</th>
                        <th>{{ __('eys.competition_type.column_order') }}</th>
                        <th>{{ __('eys.competition_type.column_status') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($competitionTypes as $competitionType)
                        <tr>
                            <td class="ip-cell-name">{{ $competitionType->getTranslation()?->name ?? '—' }}</td>
                            <td><code>{{ $competitionType->code }}</code></td>
                            <td>{{ $competitionType->sort_order }}</td>
                            <td>
                                <span class="ip-badge {{ $competitionType->status ? 'is-active' : 'is-inactive' }}">
                                    {{ $competitionType->status ? __('eys.competition_type.status_active') : __('eys.competition_type.status_inactive') }}
                                </span>
                            </td>
                            <td class="ip-table-actions">
                                <a href="{{ route('eys.competition-types.edit', $competitionType) }}" class="ip-row-icon-btn" title="{{ __('eys.users.edit_action') }}" aria-label="{{ __('eys.users.edit_action') }}">
                                    <x-eys.icon name="edit" />
                                </a>
                                <form method="POST" action="{{ route('eys.competition-types.destroy', $competitionType) }}" class="ip-inline-form">
                                    @csrf
                                    @method('DELETE')
                                    <button type="button" class="ip-row-icon-btn" title="{{ __('eys.competition_type.delete_action') }}" aria-label="{{ __('eys.competition_type.delete_action') }}" onclick="eysConfirm(@js(__('eys.competition_type.delete_confirm')), this.closest('form'))">
                                        <x-eys.icon name="trash" />
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="ip-table-empty">{{ __('eys.competition_type.empty') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $competitionTypes->onEachSide(1)->links('vendor.pagination.eys') }}
    </div>
</x-eys.app-layout>
