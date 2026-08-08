<x-institution.guest-layout
    :heading="__('institution.login.heading')"
    :subheading="__('institution.login.subheading')"
>
    <div class="ia-card-label ia-rise ia-d3">{{ __('institution.login.card_label') }}</div>
    <h2 class="ia-card-title ia-rise ia-d3">{{ __('institution.login.card_title') }}</h2>

    <x-institution.session-status class="ia-rise ia-d3" :status="session('status')" />

    <form method="POST" action="{{ route('institution.login') }}" class="ia-rise ia-d3">
        @csrf

        <div class="ia-field">
            <x-institution.label for="email" :value="__('institution.login.email')" />
            <x-institution.input id="email" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" :placeholder="__('institution.login.email_placeholder')" />
            <x-institution.input-error :messages="$errors->get('email')" />
        </div>

        <div class="ia-field">
            <x-institution.label for="password" :value="__('institution.login.password')" />
            <x-institution.input id="password" type="password" name="password" required autocomplete="current-password" placeholder="••••••••" />
            <x-institution.input-error :messages="$errors->get('password')" />
        </div>

        <div class="ia-row">
            <label for="remember_me" class="ia-check">
                <input id="remember_me" type="checkbox" name="remember">
                {{ __('institution.login.remember') }}
            </label>

            @if (Route::has('institution.password.request'))
                <a class="ia-link" href="{{ route('institution.password.request') }}">{{ __('institution.login.forgot_password') }}</a>
            @endif
        </div>

        <x-institution.button>{{ __('institution.login.submit') }} →</x-institution.button>
    </form>
</x-institution.guest-layout>
