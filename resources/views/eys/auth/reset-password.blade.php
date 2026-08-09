<x-eys.guest-layout
    :heading="__('eys.reset_password.heading')"
    :subheading="__('eys.reset_password.subheading')"
>
    <div class="ia-card-label ia-rise ia-d3">{{ __('eys.reset_password.card_label') }}</div>
    <h2 class="ia-card-title ia-rise ia-d3">{{ __('eys.reset_password.card_title') }}</h2>

    <form method="POST" action="{{ route('eys.password.store') }}" class="ia-rise ia-d3">
        @csrf

        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <div class="ia-field">
            <x-eys.label for="email" :value="__('eys.reset_password.email')" />
            <x-eys.input id="email" type="email" name="email" :value="old('email', $request->email)" required autofocus autocomplete="email" />
            <x-eys.input-error :messages="$errors->get('email')" />
        </div>

        <div class="ia-field">
            <x-eys.label for="password" :value="__('eys.reset_password.new_password')" />
            <x-eys.input id="password" type="password" name="password" required autocomplete="new-password" placeholder="••••••••" />
            <x-eys.input-error :messages="$errors->get('password')" />
        </div>

        <div class="ia-field">
            <x-eys.label for="password_confirmation" :value="__('eys.reset_password.confirm_password')" />
            <x-eys.input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password" placeholder="••••••••" />
            <x-eys.input-error :messages="$errors->get('password_confirmation')" />
        </div>

        <x-eys.button>{{ __('eys.reset_password.submit') }} →</x-eys.button>

        <div class="ia-foot">
            <a class="ia-link" href="{{ route('eys.login') }}">{{ __('eys.reset_password.back_to_login') }}</a>
        </div>
    </form>
</x-eys.guest-layout>
