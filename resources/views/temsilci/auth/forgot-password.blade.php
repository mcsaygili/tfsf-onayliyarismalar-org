<x-temsilci.guest-layout
    :heading="__('temsilci.forgot_password.heading')"
    :subheading="__('temsilci.forgot_password.subheading')"
>
    <div class="ia-card-label ia-rise ia-d3">{{ __('temsilci.forgot_password.card_label') }}</div>
    <h2 class="ia-card-title ia-rise ia-d3">{{ __('temsilci.forgot_password.card_title') }}</h2>

    <x-temsilci.session-status class="ia-rise ia-d3" :status="session('status')" />

    <form method="POST" action="{{ route('temsilci.password.email') }}" class="ia-rise ia-d3" novalidate autocomplete="off">
        @csrf

        <div class="ia-field">
            <x-temsilci.label for="email" :value="__('temsilci.forgot_password.email')" />
            <x-temsilci.input id="email" type="email" name="email" :value="old('email')" autofocus autocomplete="off" :placeholder="__('temsilci.forgot_password.email_placeholder')" />
            <x-temsilci.input-error :messages="$errors->get('email')" />
        </div>

        <x-temsilci.button>{{ __('temsilci.forgot_password.submit') }} →</x-temsilci.button>

        <div class="ia-foot">
            <a class="ia-link" href="{{ route('temsilci.login') }}">{{ __('temsilci.forgot_password.back_to_login') }}</a>
        </div>
    </form>
</x-temsilci.guest-layout>
