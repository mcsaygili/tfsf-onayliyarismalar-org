<x-institution.guest-layout
    :heading="__('institution.reset_password.heading')"
    :subheading="__('institution.reset_password.subheading')"
>
    <div class="ia-card-label ia-rise ia-d3">{{ __('institution.reset_password.card_label') }}</div>
    <h2 class="ia-card-title ia-rise ia-d3">{{ __('institution.reset_password.card_title') }}</h2>

    <form method="POST" action="{{ route('institution.password.store') }}" class="ia-rise ia-d3">
        @csrf

        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <div class="ia-field">
            <x-institution.label for="email" :value="__('institution.reset_password.email')" />
            <x-institution.input id="email" type="email" name="email" :value="old('email', $request->email)" required autofocus autocomplete="username" />
            <x-institution.input-error :messages="$errors->get('email')" />
        </div>

        <div class="ia-field">
            <x-institution.label for="password" :value="__('institution.reset_password.new_password')" />
            <x-institution.input id="password" type="password" name="password" required autocomplete="new-password" placeholder="••••••••" />
            <x-institution.input-error :messages="$errors->get('password')" />
        </div>

        <div class="ia-field">
            <x-institution.label for="password_confirmation" :value="__('institution.reset_password.confirm_password')" />
            <x-institution.input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password" placeholder="••••••••" />
            <x-institution.input-error :messages="$errors->get('password_confirmation')" />
        </div>

        <x-institution.button>{{ __('institution.reset_password.submit') }} →</x-institution.button>

        <div class="ia-foot">
            <a class="ia-link" href="{{ route('institution.login') }}">{{ __('institution.reset_password.back_to_login') }}</a>
        </div>
    </form>
</x-institution.guest-layout>
