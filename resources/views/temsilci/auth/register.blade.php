<x-temsilci.guest-layout
    :heading="__('temsilci.register.heading')"
    :subheading="__('temsilci.register.subheading')"
>
    <div class="ia-card-label ia-rise ia-d3">{{ __('temsilci.register.card_label') }}</div>
    <h2 class="ia-card-title ia-rise ia-d3">{{ __('temsilci.register.card_title') }}</h2>

    <form method="POST" action="{{ route('temsilci.register') }}" class="ia-rise ia-d3" novalidate autocomplete="off">
        @csrf

        <div class="ia-field">
            <x-temsilci.label for="email" :value="__('temsilci.register.email')" />
            <x-temsilci.input id="email" type="email" name="email" :value="old('email')" autofocus autocomplete="off" :placeholder="__('temsilci.register.email_placeholder')" />
            <x-temsilci.input-error :messages="$errors->get('email')" />
        </div>

        <div class="ia-field">
            <x-temsilci.label for="password" :value="__('temsilci.register.password')" />
            <x-temsilci.input id="password" type="password" name="password" autocomplete="off" placeholder="••••••••" />
            <x-temsilci.input-error :messages="$errors->get('password')" />
        </div>

        <div class="ia-field">
            <x-temsilci.label for="password_confirmation" :value="__('temsilci.register.confirm_password')" />
            <x-temsilci.input id="password_confirmation" type="password" name="password_confirmation" autocomplete="off" placeholder="••••••••" />
            <x-temsilci.input-error :messages="$errors->get('password_confirmation')" />
        </div>

        <x-temsilci.button>{{ __('temsilci.register.submit') }} →</x-temsilci.button>

        <div class="ia-foot">
            <a class="ia-link" href="{{ route('temsilci.login') }}">{{ __('temsilci.register.have_account') }}</a>
        </div>
    </form>
</x-temsilci.guest-layout>
