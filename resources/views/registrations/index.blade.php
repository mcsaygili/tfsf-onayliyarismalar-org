<x-dynamic-component :component="$panel.'.app-layout'" :title="__('registration.heading')">
    <h1>{{ __('registration.heading') }}</h1>
    @foreach($directCompetitions as $competition)<p><a href="{{ route($panel.'.registrations.direct.create', $competition) }}">{{ __('registration.exception_direct') }} · {{ $competition->name }}</a></p>@endforeach
    @forelse($registrations as $registration)
        <section class="ip-card"><h2>{{ $registration->competition->name }}</h2><p>{{ $registration->user->first_name }} {{ $registration->user->last_name }} · {{ __('registration.number') }} {{ $registration->number }} · {{ __('registration.'.$registration->status) }}</p><a href="{{ route($panel.'.registrations.show', $registration) }}">{{ __('registration.review') }}</a></section>
    @empty<p>{{ __('registration.empty') }}</p>@endforelse
    {{ $registrations->links() }}
</x-dynamic-component>
