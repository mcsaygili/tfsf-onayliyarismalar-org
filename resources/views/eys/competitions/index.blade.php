<x-eys.app-layout :title="__('eys.competitions.title')">
    <div class="ip-panel-stack">
        <x-eys.breadcrumb :crumbs="[
            ['label' => __('eys.nav.dashboard'), 'url' => route('eys.dashboard')],
            ['label' => __('eys.module_names.Institution'), 'url' => route('eys.institution.dashboard')],
            ['label' => __('eys.competitions.title')],
        ]" />

        <x-eys.filter-panel :action="route('eys.competitions.index')" :reset-url="route('eys.competitions.index')" :total="$competitions->total()">
            <div class="ip-filter-field">
                <label for="filter-status">{{ __('eys.competitions.filter_status') }}</label>
                <select id="filter-status" name="status" class="ia-input" onchange="this.form.submit()">
                    <option value="">{{ __('eys.competitions.filter_all_status') }}</option>
                    @foreach (['draft', 'pending_review', 'needs_info', 'approved', 'rejected'] as $value)
                        <option value="{{ $value }}" @selected($filter['status'] === $value)>{{ __('eys.competitions.status.'.$value) }}</option>
                    @endforeach
                </select>
            </div>
        </x-eys.filter-panel>

        <div class="ip-table-wrap">
            <table class="ip-table">
                <thead>
                    <tr>
                        <th>{{ __('eys.competitions.column_name') }}</th>
                        <th>{{ __('eys.competitions.column_institution') }}</th>
                        <th>{{ __('eys.competitions.column_status') }}</th>
                        <th>{{ __('eys.competitions.column_submitted_at') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($competitions as $competition)
                        <tr>
                            <td class="ip-cell-name">{{ $competition->name ?: '—' }}</td>
                            <td>{{ $competition->institution?->name ?? '—' }}</td>
                            <td>
                                <span class="ip-badge {{ $competition->status->badgeClass() }}">
                                    {{ __('eys.competitions.status.'.$competition->status->value) }}
                                </span>
                            </td>
                            <td>{{ $competition->submitted_at?->format('d.m.Y H:i') ?? '—' }}</td>
                            <td style="text-align: right;">
                                <a href="{{ route('eys.competitions.show', $competition) }}" class="ip-row-icon-btn" title="{{ __('eys.competitions.review_action') }}" aria-label="{{ __('eys.competitions.review_action') }}">
                                    <x-eys.icon name="edit" />
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="ip-table-empty">{{ __('eys.competitions.empty') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $competitions->onEachSide(1)->links('vendor.pagination.eys') }}
    </div>
</x-eys.app-layout>
