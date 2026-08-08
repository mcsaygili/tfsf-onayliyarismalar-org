<x-guest-layout>
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <div class="mb-4 text-sm font-medium text-gray-500">{{ __('Jüri Girişi') }}</div>

    <form method="POST" action="{{ route('juri.login') }}">
        @csrf

        <div>
            <x-input-label for="email" :value="__('E-posta')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="password" :value="__('Şifre')" />
            <x-text-input id="password" class="block mt-1 w-full" type="password" name="password" required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end mt-4">
            @if (Route::has('juri.password.request'))
                <a class="underline text-sm text-gray-600 hover:text-gray-900" href="{{ route('juri.password.request') }}">
                    {{ __('Şifrenizi mi unuttunuz?') }}
                </a>
            @endif

            <x-primary-button class="ms-3">
                {{ __('Giriş Yap') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
