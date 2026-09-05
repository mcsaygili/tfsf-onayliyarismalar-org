<x-institution.app-layout :title="__('operations.title')">
    <div class="competition-operations">
        <a href="{{ route('institution.operations.index') }}">{{ __('operations.back') }}</a>
        <h1>{{ $competition->name }}</h1>
        <dl class="operations-details">
            <div><dt>{{ __('operations.institution') }}</dt><dd>{{ $competition->institution->name }}</dd></div>
            <div><dt>{{ __('operations.representative') }}</dt><dd>{{ $competition->representative ? trim($competition->representative->first_name.' '.$competition->representative->last_name) : __('operations.unknown') }}</dd></div>
            <div><dt>{{ __('operations.approval') }}</dt><dd>{{ __('institution.competitions.status.'.$competition->status->value) }}</dd></div>
            <div><dt>{{ __('operations.publication') }}</dt><dd>{{ __('operations.publication_labels.'.$competition->publication_state->value) }}</dd></div>
            @foreach(['application', 'evaluation'] as $dateType)<div><dt>{{ __('operations.'.$dateType) }}</dt><dd>{{ $competition->{$dateType.'_starts_at'}?->format('d.m.Y H:i') ?? __('operations.unknown') }} — {{ $competition->{$dateType.'_ends_at'}?->format('d.m.Y H:i') ?? __('operations.unknown') }}</dd></div>@endforeach
        </dl>
        <nav class="operations-links" aria-label="{{ __('operations.title') }}"><a href="#participants">{{ __('operations.participants') }}</a><a href="#statistics">{{ __('operations.summary') }}</a><a href="{{ route('institution.registrations.index') }}">{{ __('operations.registration') }}</a><a href="{{ route('institution.participant-submissions.index') }}">{{ __('operations.photo_approvals') }}</a></nav>
        <p>{{ __('operations.live_hint') }}</p>
        <form method="GET" class="operations-filters">
            <div><label for="category">{{ __('operations.category') }}</label><select id="category" name="category" class="ia-input"><option value="">{{ __('operations.all') }}</option>@foreach($competition->categories as $category)<option value="{{ $category->id }}" @selected(($filters['category'] ?? '') === $category->id)>{{ $category->name }}</option>@endforeach</select>@error('category')<p role="alert">{{ $message }}</p>@enderror</div>
            <div><label for="status">{{ __('operations.status') }}</label><select id="status" name="status" class="ia-input"><option value="">{{ __('operations.all') }}</option>@foreach(\App\Enums\CompetitionSubmissionStatus::cases() as $status)<option value="{{ $status->value }}" @selected(($filters['status'] ?? '') === $status->value)>{{ __('operations.status_labels.'.$status->value) }}</option>@endforeach</select>@error('status')<p role="alert">{{ $message }}</p>@enderror</div>
            <button type="submit" class="ia-btn">{{ __('operations.filter') }}</button><a href="{{ route('institution.operations.show', $competition) }}">{{ __('operations.reset') }}</a>
        </form>
        <section id="statistics"><h2>{{ __('operations.summary') }}</h2><p>{{ __('operations.counts_hint') }}</p>
            <dl class="operations-totals">@foreach(['participants', 'submissions', 'photos'] as $metric)<div><dt>{{ __('operations.'.$metric) }}</dt><dd data-total="{{ $metric }}">{{ $statistics[$metric] }}</dd></div>@endforeach</dl>
            <div class="operations-table" tabindex="0" role="region" aria-label="{{ __('operations.categories') }}"><table><caption>{{ __('operations.categories') }}</caption><thead><tr><th scope="col">{{ __('operations.category') }}</th><th scope="col">{{ __('operations.participants') }}</th><th scope="col">{{ __('operations.submissions') }}</th><th scope="col">{{ __('operations.photos') }}</th></tr></thead><tbody>@foreach($statistics['categories'] as $row)<tr><th scope="row">{{ $row['name'] }}</th><td>{{ $row['participants'] }}</td><td>{{ $row['submissions'] }}</td><td>{{ $row['photos'] }}</td></tr>@endforeach</tbody></table></div>
            <h3>{{ __('operations.statuses') }}</h3><dl class="operations-totals">@foreach($statistics['statuses'] as $status => $count)<div><dt>{{ __('operations.status_labels.'.$status) }}</dt><dd>{{ $count }}</dd></div>@endforeach</dl>
        </section>
        <section id="participants"><h2>{{ __('operations.participants') }} ({{ $participants->total() }})</h2><p>{{ __('operations.profile_hint') }}</p>
            @forelse($participants as $entry)<article class="operations-row" data-entry="{{ $entry->id }}"><h3>{{ $entry->user ? trim($entry->user->first_name.' '.$entry->user->last_name) : __('operations.unknown') }}</h3>
                <p>{{ __('operations.location') }}: {{ $entry->user?->country?->short_name ?? __('operations.unknown') }} / {{ $entry->user?->city?->official_name ?? __('operations.unknown') }}</p>
                <p>{{ __('operations.entry_status') }}: {{ __('operations.status_labels.'.$entry->status->value) }} · {{ __('operations.date') }}: {{ $entry->submitted_at->format('d.m.Y H:i') }}</p>
                <ul>@foreach($entry->submissions as $submission)<li>{{ $submission->category->name }} · {{ __('operations.status_labels.'.$submission->status->value) }} · {{ __('operations.photos') }}: {{ $submission->active_photos_count }}</li>@endforeach</ul>
            </article>@empty<p class="operations-empty">{{ __('operations.no_participants') }}</p>@endforelse
            {{ $participants->links() }}
        </section>
    </div>
</x-institution.app-layout>
