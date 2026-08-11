<x-eys.app-layout :title="__('eys.system_settings.maintenance_title')">
    <div class="ip-card" style="margin-bottom: 1.5rem;">
        <div class="ip-section-title">{{ __('eys.system_settings.maintenance_title') }}</div>
        <div class="ip-section-hint" style="margin-bottom: 0;">{{ __('eys.system_settings.maintenance_hint') }}</div>
    </div>

    <form method="POST" action="{{ route('eys.system-settings.maintenance.update') }}" novalidate autocomplete="off">
        @csrf
        @method('PATCH')

        @foreach ($modules as $module)
            @php $mode = $settings->get($module); @endphp
            <div class="ip-card" style="margin-bottom: 1.5rem;">
                <div class="ip-grid-2">
                    <div class="ia-field" style="margin-bottom: 0;" x-data="{ active: {{ old("modules.$module.enabled", (int) ($mode->enabled ?? false)) ? 'true' : 'false' }} }">
                        <x-eys.label :value="__('eys.system_settings.module_'.$module)" />
                        <label class="ip-switch">
                            <input type="hidden" name="modules[{{ $module }}][enabled]" :value="active ? 1 : 0">
                            <input type="checkbox" class="ip-switch-checkbox" x-model="active">
                            <span class="ip-switch-track"><span class="ip-switch-thumb"></span></span>
                            <span class="ip-switch-label" x-text="active ? @js(__('eys.system_settings.maintenance_active')) : @js(__('eys.system_settings.maintenance_inactive'))"></span>
                        </label>
                        <x-eys.input-error :messages="$errors->get(\"modules.$module.enabled\")" />
                    </div>
                    <div class="ia-field" style="margin-bottom: 0;">
                        <x-eys.label :for="'message_'.$module" :value="__('eys.system_settings.maintenance_message')" />
                        <textarea id="message_{{ $module }}" name="modules[{{ $module }}][message]" class="ia-input" rows="2" placeholder="{{ __('eys.system_settings.maintenance_message_placeholder') }}">{{ old("modules.$module.message", $mode->message ?? '') }}</textarea>
                        <x-eys.input-error :messages="$errors->get(\"modules.$module.message\")" />
                    </div>
                </div>
            </div>
        @endforeach

        <div style="margin-top: 1.5rem;">
            <x-eys.button>{{ __('eys.system_settings.save') }}</x-eys.button>
        </div>
    </form>
</x-eys.app-layout>
