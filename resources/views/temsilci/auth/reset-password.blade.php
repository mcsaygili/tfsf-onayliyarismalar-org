<x-temsilci.guest-layout
    :heading="__('temsilci.reset_password.heading')"
    :subheading="__('temsilci.reset_password.subheading')"
>
    <div class="ia-card-label ia-rise ia-d3">{{ __('temsilci.reset_password.card_label') }}</div>
    <h2 class="ia-card-title ia-rise ia-d3">{{ __('temsilci.reset_password.card_title') }}</h2>

    <form method="POST" action="{{ route('temsilci.password.store') }}" class="ia-rise ia-d3">
        @csrf

        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <div class="ia-field">
            <x-temsilci.label for="email" :value="__('temsilci.reset_password.email')" />
            <x-temsilci.input id="email" type="email" name="email" :value="old('email', $request->email)" required autofocus autocomplete="email" />
            <x-temsilci.input-error :messages="$errors->get('email')" />
        </div>

        <div class="ia-field">
            <x-temsilci.label for="password" :value="__('temsilci.reset_password.new_password')" />
            <x-temsilci.input id="password" type="password" name="password" required autocomplete="new-password" placeholder="••••••••" />
            <x-temsilci.input-error :messages="$errors->get('password')" />
        </div>

        <div class="ia-field">
            <x-temsilci.label for="password_confirmation" :value="__('temsilci.reset_password.confirm_password')" />
            <x-temsilci.input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password" placeholder="••••••••" />
            <x-temsilci.input-error :messages="$errors->get('password_confirmation')" />
        </div>

        <x-temsilci.button>{{ __('temsilci.reset_password.submit') }} →</x-temsilci.button>

        <div class="ia-foot">
            <a class="ia-link" href="{{ route('temsilci.login') }}">{{ __('temsilci.reset_password.back_to_login') }}</a>
        </div>
    </form>
</x-temsilci.guest-layout>
