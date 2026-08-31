<x-eys.app-layout :title="$template->name">
    @include('eys.mail-client._nav', ['active' => 'templates'])

    <div class="ip-card" x-data="{ locale: 'tr' }">
        <div class="ip-section-title">{{ $template->name }}</div>
        <div class="ip-section-hint">{{ $template->description }}</div>
        <div style="margin-top:.8rem;color:var(--ia-muted);font-size:.82rem;">
            {{ __('eys.mail_client.available_variables') }}:
            @foreach($template->variables ?? [] as $variable)<code style="margin-left:.35rem;">@{{{{ $variable }}}}</code>@endforeach
        </div>

        <form method="POST" action="{{ route('eys.mail-client.templates.update', $template) }}" style="margin-top:1.25rem;">
            @csrf @method('PATCH')
            <input type="hidden" name="is_active" value="0">
            <label style="display:flex;align-items:center;gap:.55rem;margin-bottom:1rem;"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $template->is_active))> {{ __('eys.mail_client.template_active') }}</label>

            <div style="display:flex;gap:.5rem;border-bottom:1px solid var(--ia-surface-border);margin-bottom:1rem;">
                <button type="button" class="ia-btn ia-btn-secondary ip-btn-sm" @click="locale='tr'" :style="locale === 'tr' ? 'border-color:var(--ia-copper-bright)' : ''">Türkçe</button>
                <button type="button" class="ia-btn ia-btn-secondary ip-btn-sm" @click="locale='en'" :style="locale === 'en' ? 'border-color:var(--ia-copper-bright)' : ''">English</button>
            </div>

            @foreach(['tr' => 'Türkçe', 'en' => 'English'] as $locale => $label)
                @php($translation = $template->translation($locale))
                <div x-show="locale === '{{ $locale }}'" x-cloak>
                    <div class="ia-field"><x-eys.label :for="'subject-'.$locale" :value="__('eys.mail_client.template_subject')" /><input class="ia-input" id="subject-{{ $locale }}" name="translations[{{ $locale }}][subject]" value="{{ old('translations.'.$locale.'.subject', $translation?->subject) }}" required maxlength="255"><x-eys.input-error :messages="$errors->get('translations.'.$locale.'.subject')" /></div>
                    <div class="ia-field"><x-eys.label :for="'greeting-'.$locale" :value="__('eys.mail_client.template_greeting')" /><input class="ia-input" id="greeting-{{ $locale }}" name="translations[{{ $locale }}][greeting]" value="{{ old('translations.'.$locale.'.greeting', $translation?->greeting) }}" maxlength="255"></div>
                    <div class="ia-field"><x-eys.label :for="'body-'.$locale" :value="__('eys.mail_client.template_body')" /><textarea class="ia-input" id="body-{{ $locale }}" name="translations[{{ $locale }}][body]" rows="8" required maxlength="10000">{{ old('translations.'.$locale.'.body', $translation?->body) }}</textarea><x-eys.input-error :messages="$errors->get('translations.'.$locale.'.body')" /></div>
                    <div class="ia-field"><x-eys.label :for="'action-'.$locale" :value="__('eys.mail_client.template_action')" /><input class="ia-input" id="action-{{ $locale }}" name="translations[{{ $locale }}][action_label]" value="{{ old('translations.'.$locale.'.action_label', $translation?->action_label) }}" maxlength="255"></div>
                </div>
            @endforeach

            <div style="display:flex;justify-content:flex-end;gap:.6rem;margin-top:1rem;"><a class="ia-btn ia-btn-secondary" href="{{ route('eys.mail-client.templates.index') }}">{{ __('eys.common.cancel') }}</a><x-eys.button>{{ __('eys.mail_client.save') }}</x-eys.button></div>
        </form>
    </div>
</x-eys.app-layout>
