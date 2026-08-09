<x-temsilci.guest-layout
    :heading="__('temsilci.login.heading')"
    :subheading="__('temsilci.login.subheading')"
>
    <div class="ia-card-label ia-rise ia-d3">{{ __('temsilci.login.card_label') }}</div>
    <h2 class="ia-card-title ia-rise ia-d3">{{ __('temsilci.login.card_title') }}</h2>

    <x-temsilci.session-status class="ia-rise ia-d3" :status="session('status')" />

    <form method="POST" action="{{ route('temsilci.login') }}" class="ia-rise ia-d3" novalidate autocomplete="off">
        @csrf

        <div class="ia-field">
            <x-temsilci.label for="email" :value="__('temsilci.login.email')" />
            <x-temsilci.input id="email" type="email" name="email" :value="old('email')" autofocus autocomplete="off" :placeholder="__('temsilci.login.email_placeholder')" />
            <x-temsilci.input-error :messages="$errors->get('email')" />
        </div>

        <div class="ia-field">
            <x-temsilci.label for="password" :value="__('temsilci.login.password')" />
            <x-temsilci.input id="password" type="password" name="password" autocomplete="off" placeholder="••••••••" />
            <x-temsilci.input-error :messages="$errors->get('password')" />
        </div>

        <div class="ia-row">
            <label for="remember_me" class="ia-check">
                <input id="remember_me" type="checkbox" name="remember">
                {{ __('temsilci.login.remember') }}
            </label>

            @if (Route::has('temsilci.password.request'))
                <a class="ia-link" href="{{ route('temsilci.password.request') }}">{{ __('temsilci.login.forgot_password') }}</a>
            @endif
        </div>

        <x-temsilci.button>{{ __('temsilci.login.submit') }} →</x-temsilci.button>

        @if (Route::has('temsilci.register'))
            <div class="ia-foot">
                <a class="ia-link" href="{{ route('temsilci.register') }}">{{ __('temsilci.login.no_account') }}</a>
            </div>
        @endif
    </form>
</x-temsilci.guest-layout>
