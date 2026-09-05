<x-eys.app-layout :title="__('secretariat.'.($account ? 'edit' : 'new'))">
    <div class="registration-exception"><a href="{{ route('eys.secretariats.index') }}">{{ __('secretariat.title') }}</a><h1>{{ __('secretariat.'.($account ? 'edit' : 'new')) }}</h1>
    <p>{{ __('secretariat.hint') }}</p><p>{{ __('secretariat.verification_hint') }}</p>
    @if($errors->any())<div role="alert">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
    <form method="POST" action="{{ $account ? route('eys.secretariats.update', $account) : route('eys.secretariats.store') }}" class="ip-card">@csrf
        @if($account) @method('PATCH')<input type="hidden" name="context" value="{{ $context }}">@endif
        @foreach(['first_name' => 'name', 'last_name' => 'surname', 'email' => 'email', 'phone' => 'phone'] as $field => $label)
        <div class="ia-field"><label for="{{ $field }}">{{ __('secretariat.'.$label) }}</label><input class="ia-input" id="{{ $field }}" name="{{ $field }}" type="{{ $field === 'email' ? 'email' : 'text' }}" value="{{ old($field, $account?->$field) }}" maxlength="{{ $field === 'phone' ? 50 : 255 }}" @required($field !== 'phone')></div>
        @endforeach
        <div class="ia-field"><label for="status">{{ __('secretariat.status') }}</label><select class="ia-input" id="status" name="status"><option value="1" @selected(old('status', $account?->status ?? true))>{{ __('secretariat.active') }}</option><option value="0" @selected(!old('status', $account?->status ?? true))>{{ __('secretariat.inactive') }}</option></select></div>
        @unless($account) @foreach(['password', 'password_confirmation'] as $field)<div class="ia-field"><label for="{{ $field }}">{{ __('secretariat.'.$field) }}</label><input class="ia-input" type="password" id="{{ $field }}" name="{{ $field }}" required autocomplete="new-password"></div>@endforeach @endunless
        <button class="ia-btn">{{ __('secretariat.save') }}</button>
    </form></div>
</x-eys.app-layout>
