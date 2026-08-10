<x-eys.app-layout :title="__('eys.system_settings.portfolio_title')">
    <div class="ip-card">
        <div class="ip-section-title">{{ __('eys.system_settings.portfolio_title') }}</div>
        <div class="ip-section-hint">{{ __('eys.system_settings.portfolio_hint') }}</div>

        <form method="POST" action="{{ route('eys.system-settings.portfolio.update') }}" novalidate autocomplete="off">
            @csrf
            @method('PATCH')

            <div class="ia-field" style="max-width: 20rem;">
                <x-eys.label for="max_photos_per_user" :value="__('eys.system_settings.max_photos_per_user')" />
                <x-eys.input id="max_photos_per_user" type="number" name="max_photos_per_user" min="1" max="1000" :value="old('max_photos_per_user', $settings->max_photos_per_user)" autocomplete="off" required />
                <div style="font-size: .78rem; color: var(--ia-muted-dim); margin-top: .35rem;">{{ __('eys.system_settings.max_photos_per_user_hint') }}</div>
                <x-eys.input-error :messages="$errors->get('max_photos_per_user')" />
            </div>

            <div style="margin-top: 1.5rem;">
                <x-eys.button>{{ __('eys.system_settings.save') }}</x-eys.button>
            </div>
        </form>
    </div>
</x-eys.app-layout>
