<x-guest-layout>
    <div class="mb-4 text-sm text-gray-600">
        {{ __('Şifrenizi mi unuttunuz? Sorun değil. E-posta adresinizi bildirin, size yeni bir şifre belirlemeniz için bir bağlantı gönderelim.') }}
    </div>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}">
        @csrf

        <div>
            <x-input-label for="email" :value="__('E-posta')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="flex items-center justify-between mt-4">
            @if (Route::has('password.sms.request'))
                <a class="underline text-sm text-gray-600 hover:text-gray-900" href="{{ route('password.sms.request') }}">
                    {{ __('SMS ile sıfırlamak istiyorum') }}
                </a>
            @endif

            <x-primary-button>
                {{ __('Şifre Sıfırlama Bağlantısı Gönder') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
