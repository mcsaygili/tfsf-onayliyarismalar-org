<x-institution.app-layout :title="__('secretariat.profile')">
    <div class="registration-exception"><h1>{{ __('secretariat.profile') }}</h1><p>{{ $account->email }}</p>
    @if($errors->any())<div role="alert">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
    <form method="POST" action="{{ route('institution.secretariat.profile.update') }}" class="ip-card">@csrf @method('PATCH')<input type="hidden" name="context" value="{{ $context }}">
        @foreach(['first_name' => 'name', 'last_name' => 'surname', 'phone' => 'phone'] as $field => $label)<div class="ia-field"><label for="{{ $field }}">{{ __('secretariat.'.$label) }}</label><input class="ia-input" id="{{ $field }}" name="{{ $field }}" value="{{ old($field, $account->$field) }}" maxlength="{{ $field === 'phone' ? 50 : 255 }}" @required($field !== 'phone')></div>@endforeach
        <button class="ia-btn">{{ __('secretariat.save') }}</button>
    </form><p><a href="{{ route('institution.password.edit') }}">{{ __('institution.nav.password') }}</a></p></div>
</x-institution.app-layout>
