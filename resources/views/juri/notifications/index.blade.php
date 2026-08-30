<x-juri.app-layout :title="__('juri.notifications.title')">
    <header class="jp-page-heading"><div><h1>{{ __('juri.notifications.title') }}</h1><p>{{ __('juri.notifications.hint') }}</p></div>@if(auth('juri')->user()->unreadNotifications()->exists())<form method="POST" action="{{ route('juri.notifications.read-all') }}">@csrf<button class="ia-btn ia-btn-secondary">{{ __('juri.notifications.mark_all_read') }}</button></form>@endif</header>
    <div class="ip-card">
        @forelse($notifications as $notification)
            <a href="{{ route('juri.notifications.show', $notification->id) }}" style="display:block;padding:1rem;border-bottom:1px solid var(--ia-surface-border);text-decoration:none;color:inherit;background:{{ $notification->read_at ? 'transparent' : 'rgba(201,168,76,.05)' }};">
                <div style="display:flex;justify-content:space-between;gap:1rem;"><strong style="color:var(--ia-cream);">{{ $notification->data['title'] ?? __('juri.notifications.title') }}</strong><time style="color:var(--ia-muted-dim);font-size:.75rem;">{{ $notification->created_at->diffForHumans() }}</time></div>
                <p style="margin-top:.35rem;line-height:1.55;">{{ $notification->data['message'] ?? '' }}</p>
            </a>
        @empty<div class="jp-task-empty"><div><h3>{{ __('juri.notifications.empty') }}</h3></div></div>@endforelse
    </div>
    {{ $notifications->links() }}
</x-juri.app-layout>
