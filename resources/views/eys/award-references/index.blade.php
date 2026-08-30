<x-eys.app-layout :title="__('eys.award_reference.title')">
    <div class="ip-panel-stack">
        <div class="ip-page-actions">
            <x-eys.breadcrumb :crumbs="[
                ['label' => __('eys.nav.dashboard'), 'url' => route('eys.dashboard')],
                ['label' => __('eys.nav.section_competition_system')],
                ['label' => __('eys.nav.reference_data')],
                ['label' => __('eys.award_reference.title')],
            ]" />
            <a href="{{ route('eys.award-references.create') }}" class="ia-btn"><x-eys.icon name="plus" />{{ __('eys.common.add') }}</a>
        </div>

        <p class="ip-section-hint">{{ __('eys.award_reference.list_hint') }}</p>

        <x-eys.filter-panel :action="route('eys.award-references.index')" :reset-url="route('eys.award-references.index')" :total="$awards->total()">
            <x-eys.filter-search name="name" :label="__('eys.award_reference.filter_name')" :value="$filter['name'] ?? ''" />
            <div class="ip-filter-field">
                <label for="filter-kind">{{ __('eys.award_reference.kind') }}</label>
                <select id="filter-kind" name="kind" class="ia-input" onchange="this.form.submit()">
                    <option value="">{{ __('eys.award_reference.filter_all_kinds') }}</option>
                    @foreach (['award', 'exhibition', 'purchase'] as $kind)
                        <option value="{{ $kind }}" @selected(($filter['kind'] ?? '') === $kind)>{{ __('eys.award_reference.kinds.'.$kind) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="ip-filter-field">
                <label for="filter-status">{{ __('eys.award_reference.status') }}</label>
                <select id="filter-status" name="status" class="ia-input" onchange="this.form.submit()">
                    <option value="">{{ __('eys.award_reference.filter_all_status') }}</option>
                    <option value="1" @selected(($filter['status'] ?? '') === '1')>{{ __('eys.award_reference.status_active') }}</option>
                    <option value="0" @selected(($filter['status'] ?? '') === '0')>{{ __('eys.award_reference.status_inactive') }}</option>
                </select>
            </div>
        </x-eys.filter-panel>

        <div class="ip-table-wrap">
            <table class="ip-table">
                <thead><tr><th>{{ __('eys.award_reference.name') }}</th><th>{{ __('eys.award_reference.kind') }}</th><th>{{ __('eys.award_reference.code') }}</th><th>{{ __('eys.award_reference.sort_order') }}</th><th>{{ __('eys.award_reference.status') }}</th><th></th></tr></thead>
                <tbody>
                    @forelse ($awards as $award)
                        <tr>
                            <td class="ip-cell-name">{{ $award->name ?? '—' }}</td>
                            <td>{{ __('eys.award_reference.kinds.'.$award->kind) }}</td>
                            <td><code>{{ $award->code }}</code></td>
                            <td>{{ $award->sort_order }}</td>
                            <td><span class="ip-badge {{ $award->status ? 'is-active' : 'is-inactive' }}">{{ $award->status ? __('eys.award_reference.status_active') : __('eys.award_reference.status_inactive') }}</span></td>
                            <td class="ip-table-actions">
                                <a href="{{ route('eys.award-references.edit', $award) }}" class="ip-row-icon-btn" title="{{ __('eys.users.edit_action') }}" aria-label="{{ __('eys.users.edit_action') }}"><x-eys.icon name="edit" /></a>
                                @unless ($award->is_system)
                                    <form method="POST" action="{{ route('eys.award-references.destroy', $award) }}" class="ip-inline-form">@csrf @method('DELETE')<button type="button" class="ip-row-icon-btn" title="{{ __('eys.award_reference.delete_action') }}" aria-label="{{ __('eys.award_reference.delete_action') }}" onclick="eysConfirm(@js(__('eys.award_reference.delete_confirm')), this.closest('form'))"><x-eys.icon name="trash" /></button></form>
                                @endunless
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="ip-table-empty">{{ __('eys.award_reference.empty') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $awards->onEachSide(1)->links('vendor.pagination.eys') }}
    </div>
</x-eys.app-layout>
