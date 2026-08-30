<x-eys.app-layout :title="__('eys.evaluation_criterion.title')">
    <div class="ip-panel-stack">
        <div class="ip-page-actions">
            <x-eys.breadcrumb :crumbs="[['label' => __('eys.nav.dashboard'), 'url' => route('eys.dashboard')], ['label' => __('eys.nav.section_competition_system')], ['label' => __('eys.nav.reference_data')], ['label' => __('eys.evaluation_criterion.title')]]" />
            <a href="{{ route('eys.evaluation-criteria.create') }}" class="ia-btn"><x-eys.icon name="plus" />{{ __('eys.common.add') }}</a>
        </div>
        <p class="ip-section-hint">{{ __('eys.evaluation_criterion.list_hint') }}</p>
        <x-eys.filter-panel :action="route('eys.evaluation-criteria.index')" :reset-url="route('eys.evaluation-criteria.index')" :total="$criteria->total()">
            <x-eys.filter-search name="name" :label="__('eys.evaluation_criterion.filter_name')" :value="$filter['name'] ?? ''" />
            <div class="ip-filter-field"><label for="filter-status">{{ __('eys.evaluation_criterion.status') }}</label><select id="filter-status" name="status" class="ia-input" onchange="this.form.submit()"><option value="">{{ __('eys.evaluation_criterion.filter_all_status') }}</option><option value="1" @selected(($filter['status'] ?? '') === '1')>{{ __('eys.evaluation_criterion.status_active') }}</option><option value="0" @selected(($filter['status'] ?? '') === '0')>{{ __('eys.evaluation_criterion.status_inactive') }}</option></select></div>
        </x-eys.filter-panel>
        <div class="ip-table-wrap">
            <table class="ip-table">
                <thead><tr><th>{{ __('eys.evaluation_criterion.name') }}</th><th>{{ __('eys.evaluation_criterion.score_range') }}</th><th>{{ __('eys.evaluation_criterion.default_weight') }}</th><th>{{ __('eys.evaluation_criterion.code') }}</th><th>{{ __('eys.evaluation_criterion.status') }}</th><th></th></tr></thead>
                <tbody>
                    @forelse ($criteria as $criterion)
                        <tr>
                            <td class="ip-cell-name">{{ $criterion->name ?? '—' }}</td>
                            <td>{{ $criterion->default_min_score }}–{{ $criterion->default_max_score }}</td>
                            <td>{{ rtrim(rtrim($criterion->default_weight, '0'), '.') }}</td>
                            <td><code>{{ $criterion->code }}</code></td>
                            <td><span class="ip-badge {{ $criterion->status ? 'is-active' : 'is-inactive' }}">{{ $criterion->status ? __('eys.evaluation_criterion.status_active') : __('eys.evaluation_criterion.status_inactive') }}</span></td>
                            <td class="ip-table-actions"><a href="{{ route('eys.evaluation-criteria.edit', $criterion) }}" class="ip-row-icon-btn" title="{{ __('eys.users.edit_action') }}" aria-label="{{ __('eys.users.edit_action') }}"><x-eys.icon name="edit" /></a>@unless ($criterion->is_system)<form method="POST" action="{{ route('eys.evaluation-criteria.destroy', $criterion) }}" class="ip-inline-form">@csrf @method('DELETE')<button type="button" class="ip-row-icon-btn" title="{{ __('eys.evaluation_criterion.delete_action') }}" aria-label="{{ __('eys.evaluation_criterion.delete_action') }}" onclick="eysConfirm(@js(__('eys.evaluation_criterion.delete_confirm')), this.closest('form'))"><x-eys.icon name="trash" /></button></form>@endunless</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="ip-table-empty">{{ __('eys.evaluation_criterion.empty') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $criteria->onEachSide(1)->links('vendor.pagination.eys') }}
    </div>
</x-eys.app-layout>
