<x-uye.guest-layout
    :heading="__('uye.register.heading')"
    :subheading="__('uye.register.subheading')"
>
    <div class="ia-card-label ia-rise ia-d3">{{ __('uye.register.card_label') }}</div>
    <h2 class="ia-card-title ia-rise ia-d3">{{ __('uye.register.card_title') }}</h2>

    <form method="POST" action="{{ route('register') }}" class="ia-rise ia-d3" novalidate autocomplete="off">
        @csrf

        <div class="ia-field">
            <x-uye.label for="first_name" :value="__('uye.register.first_name')" />
            <x-uye.input id="first_name" type="text" name="first_name" :value="old('first_name')" autofocus autocomplete="off" />
            <x-uye.input-error :messages="$errors->get('first_name')" />
        </div>

        <div class="ia-field">
            <x-uye.label for="last_name" :value="__('uye.register.last_name')" />
            <x-uye.input id="last_name" type="text" name="last_name" :value="old('last_name')" autocomplete="off" />
            <x-uye.input-error :messages="$errors->get('last_name')" />
        </div>

        <div class="ia-field">
            <x-uye.label for="username" :value="__('uye.register.username')" />
            <x-uye.input id="username" type="text" name="username" :value="old('username')" autocomplete="off" />
            <x-uye.input-error :messages="$errors->get('username')" />
        </div>

        <div class="ia-field">
            <x-uye.label for="email" :value="__('uye.register.email')" />
            <x-uye.input id="email" type="email" name="email" :value="old('email')" autocomplete="off" :placeholder="__('uye.register.email_placeholder')" />
            <x-uye.input-error :messages="$errors->get('email')" />
        </div>

        <div class="ia-field">
            <x-uye.label for="password" :value="__('uye.register.password')" />
            <x-uye.input id="password" type="password" name="password" autocomplete="off" placeholder="••••••••" />
            <x-uye.input-error :messages="$errors->get('password')" />
        </div>

        <div class="ia-field">
            <x-uye.label for="password_confirmation" :value="__('uye.register.confirm_password')" />
            <x-uye.input id="password_confirmation" type="password" name="password_confirmation" autocomplete="off" placeholder="••••••••" />
            <x-uye.input-error :messages="$errors->get('password_confirmation')" />
        </div>

        <x-uye.button>{{ __('uye.register.submit') }} →</x-uye.button>

        <div class="ia-foot">
            <a class="ia-link" href="{{ route('login') }}">{{ __('uye.register.have_account') }}</a>
        </div>
    </form>
</x-uye.guest-layout>
