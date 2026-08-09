<x-juri.guest-layout
    :heading="__('juri.verify_email.heading')"
    :subheading="__('juri.verify_email.subheading')"
>
    <div class="ia-card-label ia-rise ia-d3">{{ __('juri.verify_email.card_label') }}</div>
    <h2 class="ia-card-title ia-rise ia-d3">{{ __('juri.verify_email.card_title') }}</h2>

    @if (session('status') == 'verification-link-sent')
        <x-juri.session-status class="ia-rise ia-d3" :status="__('juri.verify_email.resent')" />
    @endif

    <div class="ia-rise ia-d3">
        <form method="POST" action="{{ route('juri.verification.send') }}">
            @csrf
            <x-juri.button>{{ __('juri.verify_email.resend') }}</x-juri.button>
        </form>

        <div class="ia-foot">
            <form method="POST" action="{{ route('juri.logout') }}">
                @csrf
                <button type="submit" class="ia-link" style="background:none;border:none;padding:0;cursor:pointer;">{{ __('juri.verify_email.logout') }}</button>
            </form>
        </div>
    </div>
</x-juri.guest-layout>
