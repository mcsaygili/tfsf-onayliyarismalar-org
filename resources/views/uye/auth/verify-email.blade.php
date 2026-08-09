<x-uye.guest-layout
    :heading="__('uye.verify_email.heading')"
    :subheading="__('uye.verify_email.subheading')"
>
    <div class="ia-card-label ia-rise ia-d3">{{ __('uye.verify_email.card_label') }}</div>
    <h2 class="ia-card-title ia-rise ia-d3">{{ __('uye.verify_email.card_title') }}</h2>

    <p class="ia-rise ia-d3" style="font-size: .85rem; line-height: 1.6; margin-bottom: 1.5rem;">{{ __('uye.verify_email.intro') }}</p>

    @if (session('status') == 'verification-link-sent')
        <x-uye.session-status class="ia-rise ia-d3" :status="__('uye.verify_email.resent')" />
    @endif

    <div class="ia-rise ia-d3">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <x-uye.button>{{ __('uye.verify_email.resend') }}</x-uye.button>
        </form>

        <div class="ia-foot">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="ia-link" style="background:none;border:none;padding:0;cursor:pointer;">{{ __('uye.verify_email.logout') }}</button>
            </form>
        </div>
    </div>
</x-uye.guest-layout>
