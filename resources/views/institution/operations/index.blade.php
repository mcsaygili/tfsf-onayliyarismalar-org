<x-institution.app-layout :title="__('operations.title')">
    <div class="competition-operations">
        <h1>{{ __('operations.title') }}</h1><p>{{ __('operations.intro') }}</p>
        <form method="GET" class="operations-filters">
            <div><label for="state">{{ __('operations.state') }}</label><select id="state" name="state" class="ia-input">
                <option value="">{{ __('operations.all') }}</option>
                @foreach(['ongoing', 'results', 'cancelled'] as $state)<option value="{{ $state }}" @selected(($filters['state'] ?? '') === $state)>{{ __('operations.'.$state) }}</option>@endforeach
            </select>@error('state')<p role="alert">{{ $message }}</p>@enderror</div>
            <button type="submit" class="ia-btn">{{ __('operations.filter') }}</button>
            <a href="{{ route('institution.operations.index') }}">{{ __('operations.reset') }}</a>
        </form>
        @forelse($competitions as $competition)
            <article class="operations-row"><h2><a href="{{ route('institution.operations.show', $competition) }}">{{ $competition->name }}</a></h2>
                <p>{{ $competition->institution->name }} · {{ __('operations.publication_labels.'.$competition->publication_state->value) }} · {{ __('institution.competitions.status.'.$competition->status->value) }}</p>
                <p>{{ __('operations.application') }}: {{ $competition->application_starts_at?->format('d.m.Y H:i') ?? __('operations.unknown') }} — {{ $competition->application_ends_at?->format('d.m.Y H:i') ?? __('operations.unknown') }}</p>
                <a href="{{ route('institution.operations.show', $competition) }}">{{ __('operations.open') }}</a>
            </article>
        @empty<p class="operations-empty">{{ __('operations.empty') }}</p>@endforelse
        {{ $competitions->links() }}
    </div>
</x-institution.app-layout>
