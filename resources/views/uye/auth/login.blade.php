<x-uye.guest-layout
    :heading="__('uye.login.heading')"
    :subheading="__('uye.login.subheading')"
>
    <div class="ia-card-label ia-rise ia-d3">{{ __('uye.login.card_label') }}</div>
    <h2 class="ia-card-title ia-rise ia-d3">{{ __('uye.login.card_title') }}</h2>

    <x-uye.session-status class="ia-rise ia-d3" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="ia-rise ia-d3" novalidate autocomplete="off">
        @csrf

        <div class="ia-field">
            <x-uye.label for="email" :value="__('uye.login.email')" />
            <x-uye.input id="email" type="email" name="email" :value="old('email')" autofocus autocomplete="off" :placeholder="__('uye.login.email_placeholder')" />
            <x-uye.input-error :messages="$errors->get('email')" />
        </div>

        <div class="ia-field">
            <x-uye.label for="password" :value="__('uye.login.password')" />
            <x-uye.input id="password" type="password" name="password" autocomplete="off" placeholder="••••••••" />
            <x-uye.input-error :messages="$errors->get('password')" />
        </div>

        <div class="ia-row">
            <label for="remember_me" class="ia-check">
                <input id="remember_me" type="checkbox" name="remember">
                {{ __('uye.login.remember') }}
            </label>

            @if (Route::has('password.request'))
                <a class="ia-link" href="{{ route('password.request') }}">{{ __('uye.login.forgot_password') }}</a>
            @endif
        </div>

        <x-uye.button>{{ __('uye.login.submit') }} →</x-uye.button>

        @if (Route::has('register'))
            <div class="ia-foot">
                <a class="ia-link" href="{{ route('register') }}">{{ __('uye.login.no_account') }}</a>
            </div>
        @endif
    </form>
</x-uye.guest-layout>
