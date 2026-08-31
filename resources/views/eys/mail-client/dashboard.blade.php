<x-eys.app-layout :title="__('eys.mail_client.dashboard_title')">
    @include('eys.mail-client._nav', ['active' => 'dashboard'])

    <div class="ip-stats">
        <div class="ip-stat-card">
            <div class="ip-stat-icon"><x-eys.icon name="mail" /></div>
            <div>
                <div class="ip-stat-value">{{ $totalSent }}</div>
                <div class="ip-stat-label">{{ __('eys.mail_client.total_sent') }}</div>
            </div>
        </div>
        <a class="ip-stat-card" href="{{ route('eys.mail-client.logs', ['failed_delivery' => 1]) }}" style="text-decoration:none;">
            <div class="ip-stat-icon"><x-eys.icon name="mail" /></div>
            <div><div class="ip-stat-value">{{ $failedDeliveries }}</div><div class="ip-stat-label">{{ __('eys.mail_client.failed_deliveries') }}</div></div>
        </a>
        <div class="ip-stat-card">
            <div class="ip-stat-icon"><x-eys.icon name="mail" /></div>
            <div>
                <div class="ip-stat-value">{{ $sentToday }}</div>
                <div class="ip-stat-label">{{ __('eys.mail_client.sent_today') }}</div>
            </div>
        </div>
        <div class="ip-stat-card">
            <div class="ip-stat-icon"><x-eys.icon name="mail" /></div>
            <div>
                <div class="ip-stat-value">{{ $totalEvents }}</div>
                <div class="ip-stat-label">{{ __('eys.mail_client.total_events') }}</div>
            </div>
        </div>
    </div>

    <div class="ip-card">
        <div class="ip-section-title">{{ __('eys.mail_client.recent_logs') }}</div>

        <div class="ip-table-wrap">
            <table class="ip-table">
                <thead>
                    <tr>
                        <th>{{ __('eys.mail_client.column_to') }}</th>
                        <th>{{ __('eys.mail_client.column_subject') }}</th>
                        <th>{{ __('eys.mail_client.column_status') }}</th>
                        <th>{{ __('eys.mail_client.column_date') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($recentLogs as $log)
                        <tr>
                            <td>{{ $log->to }}</td>
                            <td>{{ $log->subject ?: '—' }}</td>
                            <td>
                                <span class="ip-badge is-active">{{ $log->status }}</span>
                            </td>
                            <td>{{ $log->created_at->format('d.m.Y H:i') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="ip-table-empty">{{ __('eys.mail_client.empty_logs') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-eys.app-layout>
