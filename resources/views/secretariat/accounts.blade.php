<x-eys.app-layout :title="__('secretariat.title')">
    <div class="registration-exception"><h1>{{ __('secretariat.title') }}</h1><p>{{ __('secretariat.hint') }}</p>
    <p><a href="{{ route('eys.secretariats.create') }}">{{ __('secretariat.new') }}</a></p>
    @forelse($accounts as $account)<section class="ip-card"><h2>{{ $account->first_name }} {{ $account->last_name }}</h2><p>{{ $account->email }} · {{ __('secretariat.'.($account->status ? 'active' : 'inactive')) }}</p><a href="{{ route('eys.secretariats.edit', $account) }}">{{ __('secretariat.edit') }}</a></section>@empty<p>{{ __('secretariat.empty') }}</p>@endforelse
    {{ $accounts->links() }}</div>
</x-eys.app-layout>
