<x-temsilci.guest-layout
    :heading="__('temsilci.verify_email.heading')"
    :subheading="__('temsilci.verify_email.subheading')"
>
    <div class="ia-card-label ia-rise ia-d3">{{ __('temsilci.verify_email.card_label') }}</div>
    <h2 class="ia-card-title ia-rise ia-d3">{{ __('temsilci.verify_email.card_title') }}</h2>

    @if (session('status') == 'verification-link-sent')
        <x-temsilci.session-status class="ia-rise ia-d3" :status="__('temsilci.verify_email.resent')" />
    @endif

    <div class="ia-rise ia-d3">
        <form method="POST" action="{{ route('temsilci.verification.send') }}">
            @csrf
            <x-temsilci.button>{{ __('temsilci.verify_email.resend') }}</x-temsilci.button>
        </form>

        <div class="ia-foot">
            <form method="POST" action="{{ route('temsilci.logout') }}">
                @csrf
                <button type="submit" class="ia-link" style="background:none;border:none;padding:0;cursor:pointer;">{{ __('temsilci.verify_email.logout') }}</button>
            </form>
        </div>
    </div>
</x-temsilci.guest-layout>
