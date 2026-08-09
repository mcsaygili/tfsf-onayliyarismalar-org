<x-uye.guest-layout
    :heading="__('uye.forgot_password.heading')"
    :subheading="__('uye.forgot_password.subheading')"
>
    <div class="ia-card-label ia-rise ia-d3">{{ __('uye.forgot_password.card_label') }}</div>
    <h2 class="ia-card-title ia-rise ia-d3">{{ __('uye.forgot_password.card_title') }}</h2>

    <x-uye.session-status class="ia-rise ia-d3" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}" class="ia-rise ia-d3" novalidate autocomplete="off">
        @csrf

        <div class="ia-field">
            <x-uye.label for="email" :value="__('uye.forgot_password.email')" />
            <x-uye.input id="email" type="email" name="email" :value="old('email')" autofocus autocomplete="off" :placeholder="__('uye.forgot_password.email_placeholder')" />
            <x-uye.input-error :messages="$errors->get('email')" />
        </div>

        <x-uye.button>{{ __('uye.forgot_password.submit') }} →</x-uye.button>

        <div class="ia-foot">
            <a class="ia-link" href="{{ route('login') }}">{{ __('uye.forgot_password.back_to_login') }}</a>
            @if (Route::has('password.sms.request'))
                <a class="ia-link" href="{{ route('password.sms.request') }}">{{ __('uye.forgot_password.sms_alternative') }}</a>
            @endif
        </div>
    </form>
</x-uye.guest-layout>
