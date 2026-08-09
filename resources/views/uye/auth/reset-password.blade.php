<x-uye.guest-layout
    :heading="__('uye.reset_password.heading')"
    :subheading="__('uye.reset_password.subheading')"
>
    <div class="ia-card-label ia-rise ia-d3">{{ __('uye.reset_password.card_label') }}</div>
    <h2 class="ia-card-title ia-rise ia-d3">{{ __('uye.reset_password.card_title') }}</h2>

    <form method="POST" action="{{ route('password.store') }}" class="ia-rise ia-d3">
        @csrf

        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <div class="ia-field">
            <x-uye.label for="email" :value="__('uye.reset_password.email')" />
            <x-uye.input id="email" type="email" name="email" :value="old('email', $request->email)" required autofocus autocomplete="username" />
            <x-uye.input-error :messages="$errors->get('email')" />
        </div>

        <div class="ia-field">
            <x-uye.label for="password" :value="__('uye.reset_password.new_password')" />
            <x-uye.input id="password" type="password" name="password" required autocomplete="new-password" placeholder="••••••••" />
            <x-uye.input-error :messages="$errors->get('password')" />
        </div>

        <div class="ia-field">
            <x-uye.label for="password_confirmation" :value="__('uye.reset_password.confirm_password')" />
            <x-uye.input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password" placeholder="••••••••" />
            <x-uye.input-error :messages="$errors->get('password_confirmation')" />
        </div>

        <x-uye.button>{{ __('uye.reset_password.submit') }} →</x-uye.button>

        <div class="ia-foot">
            <a class="ia-link" href="{{ route('login') }}">{{ __('uye.reset_password.back_to_login') }}</a>
        </div>
    </form>
</x-uye.guest-layout>
