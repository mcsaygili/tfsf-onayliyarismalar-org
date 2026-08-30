<x-temsilci.app-layout :title="__('temsilci.participant_submissions.title')">
    <div class="ip-card"><div class="ip-section-title">{{ __('temsilci.participant_submissions.title') }}</div><div class="ip-section-hint">{{ __('temsilci.participant_submissions.hint') }}</div>
        <div class="ip-table-wrap" style="margin-top:1.25rem;"><table class="ip-table"><thead><tr><th>{{ __('temsilci.participant_submissions.member') }}</th><th>{{ __('temsilci.participant_submissions.competition') }}</th><th>{{ __('temsilci.participant_submissions.category') }}</th><th>{{ __('temsilci.participant_submissions.status') }}</th><th></th></tr></thead><tbody>
        @forelse($approvals as $approval)<tr><td>{{ $approval->submission->entry->user->first_name }} {{ $approval->submission->entry->user->last_name }}</td><td>{{ $approval->submission->entry->competition->name }}</td><td>{{ $approval->submission->category->name }}</td><td>{{ __('temsilci.participant_submissions.statuses.'.$approval->status->value) }}</td><td><a href="{{ route('temsilci.participant-submissions.show', $approval) }}">{{ __('temsilci.participant_submissions.review') }} →</a></td></tr>
        @empty<tr><td colspan="5" class="ip-table-empty">{{ __('temsilci.participant_submissions.empty') }}</td></tr>@endforelse
        </tbody></table></div>{{ $approvals->links() }}
    </div>
</x-temsilci.app-layout>
