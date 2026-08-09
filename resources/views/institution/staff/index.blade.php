<x-institution.app-layout :title="__('institution.nav.staff')">
    <div class="ip-card">
        <div class="ip-toolbar">
            <div>
                <div class="ip-toolbar-title">{{ __('institution.staff.list_title') }}</div>
                <div class="ip-toolbar-hint">{{ __('institution.staff.list_hint') }}</div>
            </div>
            <a href="{{ route('institution.staff.create') }}" class="ia-btn ip-btn-sm">{{ __('institution.staff.add_new') }}</a>
        </div>

        <div class="ip-table-wrap">
            <table class="ip-table">
                <thead>
                    <tr>
                        <th>{{ __('institution.staff.column_name') }}</th>
                        <th>{{ __('institution.staff.column_email') }}</th>
                        <th>{{ __('institution.staff.column_phone') }}</th>
                        <th>{{ __('institution.staff.column_status') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($staffList as $member)
                        <tr>
                            <td class="ip-cell-name">{{ trim($member->first_name.' '.$member->last_name) ?: '—' }}</td>
                            <td>{{ $member->email }}</td>
                            <td>{{ $member->phone ?: '—' }}</td>
                            <td>
                                @if ($member->status)
                                    <span class="ip-badge is-active">{{ __('institution.staff.status_active') }}</span>
                                @else
                                    <span class="ip-badge is-inactive">{{ __('institution.staff.status_inactive') }}</span>
                                @endif
                            </td>
                            <td style="text-align: right;">
                                <a href="{{ route('institution.staff.edit', $member) }}" class="ip-row-icon-btn" title="{{ __('institution.staff.edit_action') }}" aria-label="{{ __('institution.staff.edit_action') }}">
                                    <x-institution.icon name="edit" />
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="ip-table-empty">{{ __('institution.staff.empty') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $staffList->onEachSide(1)->links('vendor.pagination.institution') }}
    </div>
</x-institution.app-layout>
