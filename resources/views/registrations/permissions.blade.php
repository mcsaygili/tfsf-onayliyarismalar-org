<x-eys.app-layout :title="__('registration.exception_permissions')">
    <div class="registration-review registration-exception">
        <a href="{{ route('eys.competitions.show', $competition) }}">{{ __('registration.back') }}</a>
        <h1>{{ __('registration.exception_permissions') }}</h1><h2>{{ $competition->name }}</h2>
        <p>{{ __('registration.exception_permissions_hint') }}</p>
        @if(session('status'))<p role="status">{{ session('status') }}</p>@endif
        @if($errors->any())<div class="ip-alert" role="alert">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
        @forelse($actors as $actor)
            @php
                $grant = $grants->first(fn ($item) => $item->actor_type === $actor::class && $item->actor_id === $actor->id);
                $inScope = app(\App\Services\RegistrationExceptionService::class)->inScope($competition, $actor);
                $type = $actor instanceof \App\Models\InstitutionStaff ? 'institution' : 'representative';
            @endphp
            <form method="POST" action="{{ route('eys.competitions.registration-permissions.store', $competition) }}" class="ip-card">@csrf
                <h2>{{ $actor->first_name }} {{ $actor->last_name }}</h2><p>{{ $actor->email }} · {{ __('registration.'.$type) }}</p>
                <p>{{ $grant?->active ? __('registration.exception_granted') : __('registration.exception_not_granted') }}@unless($inScope) · {{ __('registration.exception_out_of_scope') }}@endunless</p>
                <input type="hidden" name="actor_type" value="{{ $type }}"><input type="hidden" name="actor_id" value="{{ $actor->id }}"><input type="hidden" name="version" value="{{ $grant?->version ?? 0 }}">
                @if($inScope || $grant?->active)
                    <div class="ia-field"><label for="active-{{ $actor->id }}">{{ __('registration.decision') }}</label><select class="ia-input" id="active-{{ $actor->id }}" name="active" required><option value="">{{ __('registration.choose_decision') }}</option>@if($inScope)<option value="1">{{ __('registration.exception_grant') }}</option>@endif @if($grant)<option value="0">{{ __('registration.exception_revoke') }}</option>@endif</select></div>
                    <div class="ia-field"><label for="reason-{{ $actor->id }}">{{ __('registration.exception_reason') }}</label><textarea class="ia-input" id="reason-{{ $actor->id }}" name="reason" rows="3" required minlength="10" maxlength="2000">{{ old('actor_id') === $actor->id ? old('reason') : '' }}</textarea></div>
                    <button class="ia-btn">{{ __('registration.exception_save_permission') }}</button>
                @endif
                @if($grant)<p>{{ $grant->updated_at->format('d.m.Y H:i') }} · {{ $grant->reason }}</p>@endif
            </form>
        @empty<p>{{ __('registration.exception_no_reviewers') }}</p>@endforelse
    </div>
</x-eys.app-layout>
