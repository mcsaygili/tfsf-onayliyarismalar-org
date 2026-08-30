<x-institution.app-layout :title="__('institution.participant_submissions.review')">
    <a href="{{ route('institution.participant-submissions.index') }}" class="ip-back-link">← {{ __('institution.participant_submissions.back') }}</a>
    <div class="ip-card">
        <div class="ip-section-title">{{ $approval->submission->entry->competition->name }}</div>
        <div class="ip-section-hint">{{ $approval->submission->category->name }} · {{ $approval->submission->entry->user->first_name }} {{ $approval->submission->entry->user->last_name }}</div>
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:1rem;margin:1.25rem 0;">
            @foreach($approval->submission->photos as $photo)<figure style="margin:0;"><img src="{{ route('institution.participant-submissions.photos.show', $photo) }}" alt="" style="width:100%;aspect-ratio:4/3;object-fit:cover;border-radius:10px;"><figcaption style="margin-top:.5rem;">{{ $photo->captureDevice?->name ?? '—' }}</figcaption></figure>@endforeach
        </div>
        @if($approval->status->value === 'pending')
            <form method="POST" action="{{ route('institution.participant-submissions.decide', $approval) }}">@csrf
                <div class="ia-field"><label class="ia-label" for="note">{{ __('institution.participant_submissions.note') }}</label><textarea id="note" name="note" class="ia-input" rows="4">{{ old('note') }}</textarea><x-institution.input-error :messages="$errors->get('note')" /></div>
                <div style="display:flex;gap:.75rem;justify-content:flex-end;"><button class="ia-btn ia-btn-secondary" name="decision" value="reject">{{ __('institution.participant_submissions.reject') }}</button><button class="ia-btn" name="decision" value="approve">{{ __('institution.participant_submissions.approve') }}</button></div>
            </form>
        @else<div class="ip-alert"><div>{{ __('institution.participant_submissions.already_decided') }}</div></div>@endif
    </div>
</x-institution.app-layout>
