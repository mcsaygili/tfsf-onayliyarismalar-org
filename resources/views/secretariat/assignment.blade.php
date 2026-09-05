<x-eys.app-layout :title="__('secretariat.assignment')">
    <div class="registration-exception"><a href="{{ route('eys.competitions.show', $competition) }}">{{ __('registration.back') }}</a><h1>{{ __('secretariat.assignment') }}</h1><h2>{{ $competition->name }}</h2>
    <p>{{ __('secretariat.hint') }}</p>
    @php($current = $competition->secretariat_id ? \App\Models\InstitutionStaff::find($competition->secretariat_id) : null)
    <p>{{ __('secretariat.current') }}: {{ $current?->email ?? __('secretariat.unassigned') }}</p>
    @if($errors->any())<div role="alert">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
    <form method="POST" action="{{ route('eys.competitions.secretariat.store', $competition) }}" class="ip-card">@csrf<input type="hidden" name="version" value="{{ $competition->secretariat_version }}">
        <div class="ia-field"><label for="account_id">{{ __('secretariat.choose') }}</label><select class="ia-input" id="account_id" name="account_id"><option value="">{{ __('secretariat.none') }}</option>@foreach($accounts as $account)<option value="{{ $account->id }}" @selected($competition->secretariat_id === $account->id)>{{ $account->first_name }} {{ $account->last_name }} · {{ $account->email }}</option>@endforeach</select></div>
        <div class="ia-field"><label for="reason">{{ __('secretariat.reason') }}</label><textarea class="ia-input" id="reason" name="reason" required minlength="10" maxlength="2000" rows="3">{{ old('reason') }}</textarea><p>{{ __('secretariat.reason_hint') }}</p></div>
        <button class="ia-btn">{{ __('secretariat.save') }}</button>
    </form></div>
</x-eys.app-layout>
