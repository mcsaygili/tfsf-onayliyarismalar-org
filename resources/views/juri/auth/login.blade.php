<x-juri.guest-layout
    :heading="__('juri.login.heading')"
    :subheading="__('juri.login.subheading')"
>
    <div class="ia-card-label ia-rise ia-d3">{{ __('juri.login.card_label') }}</div>
    <h2 class="ia-card-title ia-rise ia-d3">{{ __('juri.login.card_title') }}</h2>

    <x-juri.session-status class="ia-rise ia-d3" :status="session('status')" />

    <form method="POST" action="{{ route('juri.login') }}" class="ia-rise ia-d3" novalidate autocomplete="off">
        @csrf

        <div class="ia-field">
            <x-juri.label for="email" :value="__('juri.login.email')" />
            <x-juri.input id="email" type="email" name="email" :value="old('email')" autofocus autocomplete="off" :placeholder="__('juri.login.email_placeholder')" />
            <x-juri.input-error :messages="$errors->get('email')" />
        </div>

        <div class="ia-field">
            <x-juri.label for="password" :value="__('juri.login.password')" />
            <x-juri.input id="password" type="password" name="password" autocomplete="off" placeholder="••••••••" />
            <x-juri.input-error :messages="$errors->get('password')" />
        </div>

        <div class="ia-row">
            <label for="remember_me" class="ia-check">
                <input id="remember_me" type="checkbox" name="remember">
                {{ __('juri.login.remember') }}
            </label>

            @if (Route::has('juri.password.request'))
                <a class="ia-link" href="{{ route('juri.password.request') }}">{{ __('juri.login.forgot_password') }}</a>
            @endif
        </div>

        <x-juri.button>{{ __('juri.login.submit') }} →</x-juri.button>

        @if (Route::has('juri.register'))
            <div class="ia-foot">
                <a class="ia-link" href="{{ route('juri.register') }}">{{ __('juri.login.no_account') }}</a>
            </div>
        @endif
    </form>
</x-juri.guest-layout>
