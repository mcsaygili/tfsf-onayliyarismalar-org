<x-institution.app-layout :title="__('institution.nav.competitions')">
    @unless ($institution->hasCompleteProfile())
        <div class="ip-alert ip-alert-warning">
            <x-institution.icon name="warning" />
            <div>
                <div class="ip-alert-title">{{ __('institution.competitions.incomplete_profile_title') }}</div>
                <div class="ip-alert-text">
                    {{ __('institution.competitions.incomplete_profile_text') }}
                    <a href="{{ route('institution.profile.edit') }}">{{ __('institution.competitions.incomplete_profile_link') }}</a>
                </div>
            </div>
        </div>
    @endunless

    <div class="ip-card">
        <div class="ip-toolbar">
            <div>
                <div class="ip-toolbar-title">{{ __('institution.competitions.list_title') }}</div>
                <div class="ip-toolbar-hint">{{ __('institution.competitions.list_hint') }}</div>
            </div>
            @if ($institution->hasCompleteProfile())
                <form method="POST" action="{{ route('institution.competitions.store') }}">
                    @csrf
                    <button type="submit" class="ia-btn ip-btn-sm">{{ __('institution.competitions.add_new') }}</button>
                </form>
            @else
                <a href="{{ route('institution.profile.edit') }}" class="ia-btn ip-btn-sm">{{ __('institution.competitions.complete_profile') }}</a>
            @endif
        </div>

        <div class="ip-table-wrap">
            <table class="ip-table">
                <thead>
                    <tr>
                        <th>{{ __('institution.competitions.column_name') }}</th>
                        <th>{{ __('institution.competitions.column_status') }}</th>
                        <th>{{ __('institution.competitions.column_publication') }}</th>
                        <th>{{ __('institution.competitions.column_updated') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($competitions as $competition)
                        <tr>
                            <td class="ip-cell-name">{{ $competition->name ?: __('institution.competitions.untitled') }}</td>
                            <td>
                                <span class="ip-badge {{ $competition->status->badgeClass() }}">
                                    {{ __('institution.competitions.status.'.$competition->status->value) }}
                                </span>
                            </td>
                            <td>{{ $competition->status === \App\Enums\CompetitionStatus::Approved ? __('institution.competitions.publication_states.'.$competition->publication_state->value) : '—' }}</td>
                            <td>{{ $competition->updated_at->format('d.m.Y H:i') }}</td>
                            <td style="text-align: right;">
                                <a href="{{ route('institution.competitions.step.show', [$competition, $competition->current_step]) }}" class="ip-row-icon-btn" title="{{ __('institution.competitions.open_action') }}" aria-label="{{ __('institution.competitions.open_action') }}">
                                    <x-institution.icon name="edit" />
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="ip-table-empty">{{ __('institution.competitions.empty') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $competitions->onEachSide(1)->links('vendor.pagination.institution') }}
    </div>
</x-institution.app-layout>
