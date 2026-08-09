<x-eys.guest-layout
    :heading="__('eys.forgot_password.heading')"
    :subheading="__('eys.forgot_password.subheading')"
>
    <div class="ia-card-label ia-rise ia-d3">{{ __('eys.forgot_password.card_label') }}</div>
    <h2 class="ia-card-title ia-rise ia-d3">{{ __('eys.forgot_password.card_title') }}</h2>

    <x-eys.session-status class="ia-rise ia-d3" :status="session('status')" />

    <form method="POST" action="{{ route('eys.password.email') }}" class="ia-rise ia-d3" novalidate autocomplete="off">
        @csrf

        <div class="ia-field">
            <x-eys.label for="email" :value="__('eys.forgot_password.email')" />
            <x-eys.input id="email" type="email" name="email" :value="old('email')" autofocus autocomplete="off" :placeholder="__('eys.forgot_password.email_placeholder')" />
            <x-eys.input-error :messages="$errors->get('email')" />
        </div>

        <x-eys.button>{{ __('eys.forgot_password.submit') }} →</x-eys.button>

        <div class="ia-foot">
            <a class="ia-link" href="{{ route('eys.login') }}">{{ __('eys.forgot_password.back_to_login') }}</a>
        </div>
    </form>
</x-eys.guest-layout>
