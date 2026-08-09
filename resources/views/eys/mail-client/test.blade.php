<x-eys.app-layout :title="__('eys.mail_client.test')">
    @include('eys.mail-client._nav', ['active' => 'test'])

    <div class="ip-card">
        <div class="ip-section-title">{{ __('eys.mail_client.test') }}</div>
        <div class="ip-section-hint">{{ __('eys.mail_client.test_hint') }}</div>

        <form method="POST" action="{{ route('eys.mail-client.test.send') }}" novalidate autocomplete="off">
            @csrf

            <div class="ia-field">
                <x-eys.label for="to" :value="__('eys.mail_client.test_to')" />
                <x-eys.input id="to" type="email" name="to" :value="old('to')" autocomplete="off" />
                <x-eys.input-error :messages="$errors->get('to')" />
            </div>

            <div class="ia-field">
                <x-eys.label for="subject" :value="__('eys.mail_client.test_subject')" />
                <x-eys.input id="subject" type="text" name="subject" :value="old('subject')" autocomplete="off" />
                <x-eys.input-error :messages="$errors->get('subject')" />
            </div>

            <div class="ia-field" style="margin-bottom: 0;">
                <x-eys.label for="message" :value="__('eys.mail_client.test_message')" />
                <textarea id="message" name="message" class="ia-input" rows="6">{{ old('message') }}</textarea>
                <x-eys.input-error :messages="$errors->get('message')" />
            </div>

            <div style="margin-top: 1.5rem;">
                <x-eys.button>{{ __('eys.mail_client.test_send') }}</x-eys.button>
            </div>
        </form>
    </div>
</x-eys.app-layout>
