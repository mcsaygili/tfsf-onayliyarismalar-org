<x-guest-layout>
    <div class="mb-4 text-sm text-gray-600">
        {{ __('Telefon numaranıza bir doğrulama kodu gönderelim, ardından yeni şifrenizi belirleyin.') }}
    </div>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('password.sms.send') }}" class="mb-6">
        @csrf

        <div>
            <x-input-label for="phone_number" :value="__('Telefon Numarası')" />
            <x-text-input id="phone_number" class="block mt-1 w-full" type="tel" name="phone_number" :value="old('phone_number')" required autofocus />
            <x-input-error :messages="$errors->get('phone_number')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end mt-4">
            <x-primary-button>
                {{ __('Kod Gönder') }}
            </x-primary-button>
        </div>
    </form>

    @if (session('phone_number'))
        <form method="POST" action="{{ route('password.sms.verify') }}">
            @csrf
            <input type="hidden" name="phone_number" value="{{ session('phone_number') }}">

            <div>
                <x-input-label for="code" :value="__('Doğrulama Kodu')" />
                <x-text-input id="code" class="block mt-1 w-full" type="text" name="code" required />
                <x-input-error :messages="$errors->get('code')" class="mt-2" />
            </div>

            <div class="mt-4">
                <x-input-label for="password" :value="__('Yeni Şifre')" />
                <x-text-input id="password" class="block mt-1 w-full" type="password" name="password" required autocomplete="new-password" />
                <x-input-error :messages="$errors->get('password')" class="mt-2" />
            </div>

            <div class="mt-4">
                <x-input-label for="password_confirmation" :value="__('Şifre (Tekrar)')" />
                <x-text-input id="password_confirmation" class="block mt-1 w-full" type="password" name="password_confirmation" required autocomplete="new-password" />
                <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
            </div>

            <div class="flex items-center justify-end mt-4">
                <x-primary-button>
                    {{ __('Şifreyi Sıfırla') }}
                </x-primary-button>
            </div>
        </form>
    @endif
</x-guest-layout>
