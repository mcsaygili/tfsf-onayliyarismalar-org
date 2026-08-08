<x-institution.guest-layout
    :heading="__('institution.forgot_password.heading')"
    :subheading="__('institution.forgot_password.subheading')"
>
    <div class="ia-card-label ia-rise ia-d3">{{ __('institution.forgot_password.card_label') }}</div>
    <h2 class="ia-card-title ia-rise ia-d3">{{ __('institution.forgot_password.card_title') }}</h2>

    <x-institution.session-status class="ia-rise ia-d3" :status="session('status')" />

    <form method="POST" action="{{ route('institution.password.email') }}" class="ia-rise ia-d3" novalidate autocomplete="off">
        @csrf

        <div class="ia-field">
            <x-institution.label for="email" :value="__('institution.forgot_password.email')" />
            <x-institution.input id="email" type="email" name="email" :value="old('email')" autofocus autocomplete="off" :placeholder="__('institution.forgot_password.email_placeholder')" />
            <x-institution.input-error :messages="$errors->get('email')" />
        </div>

        <x-institution.button>{{ __('institution.forgot_password.submit') }} →</x-institution.button>

        <div class="ia-foot">
            <a class="ia-link" href="{{ route('institution.login') }}">{{ __('institution.forgot_password.back_to_login') }}</a>
        </div>
    </form>
</x-institution.guest-layout>
