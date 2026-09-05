<x-institution.app-layout :title="__('secretariat.dashboard')">
    <div class="registration-exception"><h1>{{ __('secretariat.competitions') }}</h1><p>{{ __('secretariat.hint') }}</p>
    <p><a href="{{ route('institution.registrations.index') }}">{{ __('registration.heading') }}</a></p><p><a href="{{ route('institution.participant-submissions.index') }}">{{ __('institution.nav.participant_approvals') }}</a></p>
    @forelse($competitions as $competition)<section class="ip-card"><h2>{{ $competition->name }}</h2><p>{{ $competition->institution->name }} · {{ __('secretariat.entries') }}: {{ $competition->entries_count }}</p><p>{{ __('eys.competitions.status.'.$competition->status->value) }}</p></section>@empty<p>{{ __('secretariat.no_competitions') }}</p>@endforelse
    {{ $competitions->links() }}</div>
</x-institution.app-layout>
