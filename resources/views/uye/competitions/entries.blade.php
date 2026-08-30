<x-uye.app-layout :title="__('uye.competitions.my_entries')">
    <header class="mp-page-heading"><div><h1>{{ __('uye.competitions.my_entries') }}</h1><p>{{ __('uye.competitions.my_entries_hint') }}</p></div><a class="ia-btn" href="{{ route('competitions.index') }}">{{ __('uye.competitions.browse') }}</a></header>
    <div class="mp-entry-list">
        @forelse($entries as $entry)
            <a class="mp-entry-row" href="{{ route('competitions.entry.show', $entry) }}">
                <div><span>{{ $entry->competition->institution->name }}</span><h2>{{ $entry->competition->name }}</h2><p>{{ $entry->submissions->pluck('category.name')->filter()->join(', ') ?: __('uye.competitions.no_category') }}</p></div>
                <div><span class="mp-state is-{{ $entry->status->value }}">{{ __('uye.competitions.entry_status.'.$entry->status->value) }}</span><small>{{ $entry->updated_at->format('d.m.Y H:i') }}</small></div>
            </a>
        @empty
            <div class="mp-empty"><strong>{{ __('uye.competitions.no_entries_title') }}</strong><p>{{ __('uye.competitions.no_entries_text') }}</p></div>
        @endforelse
    </div>
    {{ $entries->links() }}
</x-uye.app-layout>
