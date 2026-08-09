<x-juri.guest-layout
    :heading="__('juri.register.heading')"
    :subheading="__('juri.register.subheading')"
>
    <div class="ia-card-label ia-rise ia-d3">{{ __('juri.register.card_label') }}</div>
    <h2 class="ia-card-title ia-rise ia-d3">{{ __('juri.register.card_title') }}</h2>

    <form method="POST" action="{{ route('juri.register') }}" class="ia-rise ia-d3" novalidate autocomplete="off">
        @csrf

        <div class="ia-field">
            <x-juri.label for="email" :value="__('juri.register.email')" />
            <x-juri.input id="email" type="email" name="email" :value="old('email')" autofocus autocomplete="off" :placeholder="__('juri.register.email_placeholder')" />
            <x-juri.input-error :messages="$errors->get('email')" />
        </div>

        <div class="ia-field">
            <x-juri.label for="password" :value="__('juri.register.password')" />
            <x-juri.input id="password" type="password" name="password" autocomplete="off" placeholder="••••••••" />
            <x-juri.input-error :messages="$errors->get('password')" />
        </div>

        <div class="ia-field">
            <x-juri.label for="password_confirmation" :value="__('juri.register.confirm_password')" />
            <x-juri.input id="password_confirmation" type="password" name="password_confirmation" autocomplete="off" placeholder="••••••••" />
            <x-juri.input-error :messages="$errors->get('password_confirmation')" />
        </div>

        <x-juri.button>{{ __('juri.register.submit') }} →</x-juri.button>

        <div class="ia-foot">
            <a class="ia-link" href="{{ route('juri.login') }}">{{ __('juri.register.have_account') }}</a>
        </div>
    </form>
</x-juri.guest-layout>
