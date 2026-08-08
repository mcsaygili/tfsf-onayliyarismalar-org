<x-institution.guest-layout
    :heading="__('institution.register.heading')"
    :subheading="__('institution.register.subheading')"
>
    <div class="ia-card-label ia-rise ia-d3">{{ __('institution.register.card_label') }}</div>
    <h2 class="ia-card-title ia-rise ia-d3">{{ __('institution.register.card_title') }}</h2>

    <form method="POST" action="{{ route('institution.register') }}" class="ia-rise ia-d3" novalidate autocomplete="off">
        @csrf

        <div class="ia-field">
            <x-institution.label for="email" :value="__('institution.register.email')" />
            <x-institution.input id="email" type="email" name="email" :value="old('email')" autofocus autocomplete="off" :placeholder="__('institution.register.email_placeholder')" />
            <x-institution.input-error :messages="$errors->get('email')" />
        </div>

        <div class="ia-field">
            <x-institution.label for="password" :value="__('institution.register.password')" />
            <x-institution.input id="password" type="password" name="password" autocomplete="off" placeholder="••••••••" />
            <x-institution.input-error :messages="$errors->get('password')" />
        </div>

        <div class="ia-field">
            <x-institution.label for="password_confirmation" :value="__('institution.register.confirm_password')" />
            <x-institution.input id="password_confirmation" type="password" name="password_confirmation" autocomplete="off" placeholder="••••••••" />
            <x-institution.input-error :messages="$errors->get('password_confirmation')" />
        </div>

        <x-institution.button>{{ __('institution.register.submit') }} →</x-institution.button>

        <div class="ia-foot">
            <a class="ia-link" href="{{ route('institution.login') }}">{{ __('institution.register.have_account') }}</a>
        </div>
    </form>
</x-institution.guest-layout>
