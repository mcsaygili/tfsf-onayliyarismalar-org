<x-juri.guest-layout
    :heading="__('juri.forgot_password.heading')"
    :subheading="__('juri.forgot_password.subheading')"
>
    <div class="ia-card-label ia-rise ia-d3">{{ __('juri.forgot_password.card_label') }}</div>
    <h2 class="ia-card-title ia-rise ia-d3">{{ __('juri.forgot_password.card_title') }}</h2>

    <x-juri.session-status class="ia-rise ia-d3" :status="session('status')" />

    <form method="POST" action="{{ route('juri.password.email') }}" class="ia-rise ia-d3" novalidate autocomplete="off">
        @csrf

        <div class="ia-field">
            <x-juri.label for="email" :value="__('juri.forgot_password.email')" />
            <x-juri.input id="email" type="email" name="email" :value="old('email')" autofocus autocomplete="off" :placeholder="__('juri.forgot_password.email_placeholder')" />
            <x-juri.input-error :messages="$errors->get('email')" />
        </div>

        <x-juri.button>{{ __('juri.forgot_password.submit') }} →</x-juri.button>

        <div class="ia-foot">
            <a class="ia-link" href="{{ route('juri.login') }}">{{ __('juri.forgot_password.back_to_login') }}</a>
        </div>
    </form>
</x-juri.guest-layout>
