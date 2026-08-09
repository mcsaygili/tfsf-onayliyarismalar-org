<x-juri.guest-layout
    :heading="__('juri.reset_password.heading')"
    :subheading="__('juri.reset_password.subheading')"
>
    <div class="ia-card-label ia-rise ia-d3">{{ __('juri.reset_password.card_label') }}</div>
    <h2 class="ia-card-title ia-rise ia-d3">{{ __('juri.reset_password.card_title') }}</h2>

    <form method="POST" action="{{ route('juri.password.store') }}" class="ia-rise ia-d3">
        @csrf

        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <div class="ia-field">
            <x-juri.label for="email" :value="__('juri.reset_password.email')" />
            <x-juri.input id="email" type="email" name="email" :value="old('email', $request->email)" required autofocus autocomplete="email" />
            <x-juri.input-error :messages="$errors->get('email')" />
        </div>

        <div class="ia-field">
            <x-juri.label for="password" :value="__('juri.reset_password.new_password')" />
            <x-juri.input id="password" type="password" name="password" required autocomplete="new-password" placeholder="••••••••" />
            <x-juri.input-error :messages="$errors->get('password')" />
        </div>

        <div class="ia-field">
            <x-juri.label for="password_confirmation" :value="__('juri.reset_password.confirm_password')" />
            <x-juri.input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password" placeholder="••••••••" />
            <x-juri.input-error :messages="$errors->get('password_confirmation')" />
        </div>

        <x-juri.button>{{ __('juri.reset_password.submit') }} →</x-juri.button>

        <div class="ia-foot">
            <a class="ia-link" href="{{ route('juri.login') }}">{{ __('juri.reset_password.back_to_login') }}</a>
        </div>
    </form>
</x-juri.guest-layout>
