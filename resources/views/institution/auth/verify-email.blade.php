<x-institution.guest-layout
    :heading="__('institution.verify_email.heading')"
    :subheading="__('institution.verify_email.subheading')"
>
    <div class="ia-card-label ia-rise ia-d3">{{ __('institution.verify_email.card_label') }}</div>
    <h2 class="ia-card-title ia-rise ia-d3">{{ __('institution.verify_email.card_title') }}</h2>

    @if (session('status') == 'verification-link-sent')
        <x-institution.session-status class="ia-rise ia-d3" :status="__('institution.verify_email.resent')" />
    @endif

    <div class="ia-rise ia-d3">
        <form method="POST" action="{{ route('institution.verification.send') }}">
            @csrf
            <x-institution.button>{{ __('institution.verify_email.resend') }}</x-institution.button>
        </form>

        <div class="ia-foot">
            <form method="POST" action="{{ route('institution.logout') }}">
                @csrf
                <button type="submit" class="ia-link" style="background:none;border:none;padding:0;cursor:pointer;">{{ __('institution.verify_email.logout') }}</button>
            </form>
        </div>
    </div>
</x-institution.guest-layout>
