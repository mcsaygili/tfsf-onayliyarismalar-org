<x-institution.app-layout :title="__('institution.participant_submissions.title')">
    <div class="ip-card">
        <div class="ip-section-title">{{ __('institution.participant_submissions.title') }}</div>
        <div class="ip-section-hint">{{ __('institution.participant_submissions.hint') }}</div>
        <div class="ip-table-wrap" style="margin-top: 1.25rem;"><table class="ip-table"><thead><tr><th>{{ __('institution.participant_submissions.member') }}</th><th>{{ __('institution.participant_submissions.competition') }}</th><th>{{ __('institution.participant_submissions.category') }}</th><th>{{ __('institution.participant_submissions.status') }}</th><th></th></tr></thead><tbody>
        @forelse($approvals as $approval)<tr><td>{{ $approval->submission->entry->user->first_name }} {{ $approval->submission->entry->user->last_name }}</td><td>{{ $approval->submission->entry->competition->name }}</td><td>{{ $approval->submission->category->name }}</td><td><span class="ip-badge">{{ __('institution.participant_submissions.statuses.'.$approval->status->value) }}</span></td><td><a href="{{ route('institution.participant-submissions.show', $approval) }}">{{ __('institution.participant_submissions.review') }} →</a></td></tr>
        @empty<tr><td colspan="5" class="ip-table-empty">{{ __('institution.participant_submissions.empty') }}</td></tr>@endforelse
        </tbody></table></div>
        {{ $approvals->links() }}
    </div>
</x-institution.app-layout>
