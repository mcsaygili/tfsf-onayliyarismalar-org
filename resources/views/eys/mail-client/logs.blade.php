<x-eys.app-layout :title="__('eys.mail_client.logs')">
    @include('eys.mail-client._nav', ['active' => 'logs'])

    <div class="ip-panel-stack">
        <x-eys.breadcrumb :crumbs="[
            ['label' => __('eys.nav.dashboard'), 'url' => route('eys.dashboard')],
            ['label' => __('eys.nav.mail_client'), 'url' => route('eys.mail-client.dashboard')],
            ['label' => __('eys.mail_client.logs')],
        ]" />

        <x-eys.filter-panel :action="route('eys.mail-client.logs')" :reset-url="route('eys.mail-client.logs')" :total="$logs->total()">
            <x-eys.filter-search name="to" :label="__('eys.mail_client.filter_to')" :value="$filter['to']" />
            <div class="ip-filter-field">
                <label for="filter-status">{{ __('eys.mail_client.filter_status') }}</label>
                <select id="filter-status" name="status" class="ia-input" onchange="this.form.submit()">
                    <option value="">{{ __('eys.mail_client.filter_all_status') }}</option>
                    <option value="sent" @selected($filter['status'] === 'sent')>sent</option>
                    <option value="failed" @selected($filter['status'] === 'failed')>failed</option>
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
                        <th>{{ __('eys.mail_client.column_to') }}</th>
                        <th>{{ __('eys.mail_client.column_subject') }}</th>
                        <th>{{ __('eys.mail_client.column_mailable') }}</th>
                        <th>{{ __('eys.mail_client.column_status') }}</th>
                        <th>{{ __('eys.mail_client.column_provider_id') }}</th>
                        <th>{{ __('eys.mail_client.column_date') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($logs as $log)
                        <tr>
                            <td>{{ $log->to }}</td>
                            <td>{{ $log->subject ?: '—' }}</td>
                            <td>{{ $log->mailable ? class_basename($log->mailable) : '—' }}</td>
                            <td>
                                @if ($log->status === 'sent')
                                    <span class="ip-badge is-active">{{ $log->status }}</span>
                                @else
                                    <span class="ip-badge is-inactive">{{ $log->status }}</span>
                                @endif
                            </td>
                            <td style="font-family: monospace; font-size: .8rem;">{{ $log->provider_message_id ?: '—' }}</td>
                            <td>{{ $log->created_at->format('d.m.Y H:i') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="ip-table-empty">{{ __('eys.mail_client.empty_logs') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $logs->onEachSide(1)->links('vendor.pagination.eys') }}
    </div>
</x-eys.app-layout>
