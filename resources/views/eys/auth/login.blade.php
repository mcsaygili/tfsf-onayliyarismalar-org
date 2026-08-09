<x-eys.guest-layout
    :heading="__('eys.login.heading')"
    :subheading="__('eys.login.subheading')"
>
    <div class="ia-card-label ia-rise ia-d3">{{ __('eys.login.card_label') }}</div>
    <h2 class="ia-card-title ia-rise ia-d3">{{ __('eys.login.card_title') }}</h2>

    <x-eys.session-status class="ia-rise ia-d3" :status="session('status')" />

    <form method="POST" action="{{ route('eys.login') }}" class="ia-rise ia-d3" novalidate autocomplete="off">
        @csrf

        <div class="ia-field">
            <x-eys.label for="email" :value="__('eys.login.email')" />
            <x-eys.input id="email" type="email" name="email" :value="old('email')" autofocus autocomplete="off" :placeholder="__('eys.login.email_placeholder')" />
            <x-eys.input-error :messages="$errors->get('email')" />
        </div>

        <div class="ia-field">
            <x-eys.label for="password" :value="__('eys.login.password')" />
            <x-eys.input id="password" type="password" name="password" autocomplete="off" placeholder="••••••••" />
            <x-eys.input-error :messages="$errors->get('password')" />
        </div>

        <div class="ia-row">
            <label for="remember_me" class="ia-check">
                <input id="remember_me" type="checkbox" name="remember">
                {{ __('eys.login.remember') }}
            </label>

            @if (Route::has('eys.password.request'))
                <a class="ia-link" href="{{ route('eys.password.request') }}">{{ __('eys.login.forgot_password') }}</a>
            @endif
        </div>

        <x-eys.button>{{ __('eys.login.submit') }} →</x-eys.button>

        @if (Route::has('eys.register'))
            <div class="ia-foot">
                <a class="ia-link" href="{{ route('eys.register') }}">{{ __('eys.login.no_account') }}</a>
            </div>
        @endif
    </form>
</x-eys.guest-layout>
