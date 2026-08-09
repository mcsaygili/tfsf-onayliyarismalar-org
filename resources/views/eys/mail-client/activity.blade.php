<x-eys.app-layout :title="__('eys.mail_client.activity')">
    @include('eys.mail-client._nav', ['active' => 'activity'])

    <div class="ip-panel-stack">
        <x-eys.breadcrumb :crumbs="[
            ['label' => __('eys.nav.dashboard'), 'url' => route('eys.dashboard')],
            ['label' => __('eys.nav.mail_client'), 'url' => route('eys.mail-client.dashboard')],
            ['label' => __('eys.mail_client.activity')],
        ]" />

        <x-eys.filter-panel :action="route('eys.mail-client.activity')" :reset-url="route('eys.mail-client.activity')" :total="$events->total()">
            <div class="ip-filter-field">
                <label for="filter-event-type">{{ __('eys.mail_client.filter_event_type') }}</label>
                <select id="filter-event-type" name="event_type" class="ia-input" onchange="this.form.submit()">
                    <option value="">{{ __('eys.mail_client.filter_all_event_types') }}</option>
                    @foreach ($eventTypes as $type)
                        <option value="{{ $type }}" @selected($filter['event_type'] === $type)>{{ $type }}</option>
                    @endforeach
                </select>
            </div>
            <div class="ip-filter-field">
                <label for="filter-date-from">{{ __('eys.common.filter_date_from') }}</label>
                <input type="date" id="filter-date-from" name="date_from" value="{{ $filter['date_from'] }}" class="ia-input">
            </div>
            <div class="ip-filter-field">
                <label for="filter-date-to">{{ __('eys.common.filter_date_to') }}</label>
                <input type="date" id="filter-date-to" name="date_to" value="{{ $filter['date_to'] }}" class="ia-input">
            </div>
        </x-eys.filter-panel>

        <div class="ip-table-wrap">
            <table class="ip-table">
                <thead>
                    <tr>
                        <th>{{ __('eys.mail_client.column_event_type') }}</th>
                        <th>{{ __('eys.mail_client.column_related_log') }}</th>
                        <th>{{ __('eys.mail_client.column_date') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($events as $event)
                        <tr>
                            <td class="ip-cell-name">{{ $event->event_type }}</td>
                            <td>{{ $event->mailSendLog?->to ?? '—' }}</td>
                            <td>{{ $event->created_at->format('d.m.Y H:i') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="ip-table-empty">{{ __('eys.mail_client.empty_activity') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $events->onEachSide(1)->links('vendor.pagination.eys') }}
    </div>
</x-eys.app-layout>
