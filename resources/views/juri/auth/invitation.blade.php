<x-juri.guest-layout
    :heading="__('juri.invitation.heading')"
    :subheading="__('juri.invitation.subheading', ['institution' => $invitation->institution->name])"
>
    <div class="ia-card-label ia-rise ia-d3">{{ __('juri.invitation.card_label') }}</div>
    <h2 class="ia-card-title ia-rise ia-d3">{{ $existingJuror ? __('juri.invitation.existing_card_title') : __('juri.invitation.card_title') }}</h2>

    <form method="POST" action="{{ route('juri.invitation.accept', $token) }}" class="ia-rise ia-d3" novalidate autocomplete="off">
        @csrf

        <div class="ia-field">
            <x-juri.label for="email" :value="__('juri.invitation.email')" />
            <x-juri.input id="email" type="email" :value="$invitation->email" disabled />
        </div>

        @if ($existingJuror)
            <div class="ia-status">
                {{ __('juri.invitation.existing_account_hint', ['name' => trim($existingJuror->first_name.' '.$existingJuror->last_name)]) }}
            </div>
        @else
            <div class="ia-field">
                <x-juri.label for="first_name" :value="__('juri.invitation.first_name')" />
                <x-juri.input id="first_name" type="text" name="first_name" :value="old('first_name', $invitation->first_name)" autofocus autocomplete="given-name" />
                <x-juri.input-error :messages="$errors->get('first_name')" />
            </div>

            <div class="ia-field">
                <x-juri.label for="last_name" :value="__('juri.invitation.last_name')" />
                <x-juri.input id="last_name" type="text" name="last_name" :value="old('last_name', $invitation->last_name)" autocomplete="family-name" />
                <x-juri.input-error :messages="$errors->get('last_name')" />
            </div>

            <div class="ia-field">
                <x-juri.label for="password" :value="__('juri.invitation.password')" />
                <x-juri.input id="password" type="password" name="password" autocomplete="new-password" />
                <x-juri.input-error :messages="$errors->get('password')" />
            </div>

            <div class="ia-field">
                <x-juri.label for="password_confirmation" :value="__('juri.invitation.password_confirmation')" />
                <x-juri.input id="password_confirmation" type="password" name="password_confirmation" autocomplete="new-password" />
            </div>
        @endif

        <x-juri.button>{{ $existingJuror ? __('juri.invitation.link_submit') : __('juri.invitation.submit') }} →</x-juri.button>
    </form>

    <form method="POST" action="{{ route('juri.invitation.decline', $token) }}" class="ia-rise ia-d4" style="margin-top: .8rem;">
        @csrf
        <button type="submit" class="ia-btn" style="background: transparent; color: var(--ia-muted); border: 1px solid var(--ia-surface-border);" onclick="return confirm(@js(__('juri.invitation.decline_confirm'))) ">
            {{ __('juri.invitation.decline_submit') }}
        </button>
    </form>
</x-juri.guest-layout>
