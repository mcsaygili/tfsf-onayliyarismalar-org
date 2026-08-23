<x-institution.app-layout :title="__('institution.nav.competitions')">
    @include('institution.competitions._steps')

    @if ($competition->status->value === 'needs_info' && $competition->latest_review_message)
        <div class="ip-alert ip-alert-warning">
            <x-institution.icon name="warning" />
            <div>
                <div class="ip-alert-title">{{ __('institution.competitions.needs_info_title') }}</div>
                <div class="ip-alert-text">{{ $competition->latest_review_message }}</div>
            </div>
        </div>
    @endif

    <form method="POST" action="{{ route('institution.competitions.step.update', [$competition, $step]) }}" novalidate autocomplete="off">
        @csrf
        @method('PUT')

        <div class="ip-card">
            <div class="ip-section-title">{{ __('institution.competitions.steps.2.label') }}</div>
            <div class="ip-section-hint">{{ __('institution.competitions.steps.2.hint') }}</div>

            <div class="ia-field">
                <x-institution.field-label for="name" :value="__('institution.competitions.fields.name')" :description="__('institution.competitions.field_help.name.description')" :example="__('institution.competitions.field_help.name.example')" />
                <x-institution.input id="name" type="text" name="name" :value="old('name', $competition->name)" autocomplete="off" />
                <x-institution.input-error :messages="$errors->get('name')" />
            </div>

            <div class="ia-field">
                <x-institution.field-label for="organizing_institution" :value="__('institution.competitions.fields.organizing_institution')" :description="__('institution.competitions.field_help.organizing_institution.description')" :example="$competition->institution->name" />
                <x-institution.input id="organizing_institution" type="text" :value="$competition->institution->name" readonly aria-readonly="true" />
                <div class="ip-section-hint" style="margin: .4rem 0 0;">{{ __('institution.competitions.fields.organizing_institution_hint') }}</div>
            </div>

            <div class="ia-field">
                <x-institution.field-label for="partners" :value="__('institution.competitions.fields.partners')" :description="__('institution.competitions.field_help.partners.description')" :example="__('institution.competitions.field_help.partners.example')" />
                <x-institution.input id="partners" type="text" name="partners" :value="old('partners', $competition->partners)" :placeholder="__('institution.competitions.fields.partners_placeholder')" autocomplete="off" />
                <div class="ip-section-hint" style="margin: .4rem 0 0;">{{ __('institution.competitions.fields.partners_hint') }}</div>
                <x-institution.input-error :messages="$errors->get('partners')" />
            </div>

            <div class="ia-field" x-data="{ remaining: {{ max(0, 1000 - mb_strlen((string) old('subject', $competition->subject))) }}, max: 1000 }">
                <x-institution.field-label for="subject" :value="__('institution.competitions.fields.subject')" :description="__('institution.competitions.field_help.subject.description')" :example="__('institution.competitions.field_help.subject.example')" />
                <textarea id="subject" name="subject" class="ia-input" rows="4" maxlength="1000" aria-describedby="subject-limit"
                          x-on:input="remaining = Math.max(0, max - [...$event.target.value].length)">{{ old('subject', $competition->subject) }}</textarea>
                <div id="subject-limit" class="ip-section-hint" style="margin: .4rem 0 0;" aria-live="polite"
                     x-text="@js(__('institution.competitions.fields.characters_remaining', ['remaining' => '__remaining__', 'max' => '__max__'])).replace('__remaining__', remaining).replace('__max__', max)">
                    {{ __('institution.competitions.fields.characters_remaining', ['remaining' => 1000 - mb_strlen((string) old('subject', $competition->subject)), 'max' => 1000]) }}
                </div>
                <x-institution.input-error :messages="$errors->get('subject')" />
            </div>

            <div class="ia-field" style="margin-bottom: 0;" x-data="{ remaining: {{ max(0, 1000 - mb_strlen((string) old('purpose', $competition->purpose))) }}, max: 1000 }">
                <x-institution.field-label for="purpose" :value="__('institution.competitions.fields.purpose')" :description="__('institution.competitions.field_help.purpose.description')" :example="__('institution.competitions.field_help.purpose.example')" />
                <textarea id="purpose" name="purpose" class="ia-input" rows="4" maxlength="1000" aria-describedby="purpose-limit"
                          x-on:input="remaining = Math.max(0, max - [...$event.target.value].length)">{{ old('purpose', $competition->purpose) }}</textarea>
                <div id="purpose-limit" class="ip-section-hint" style="margin: .4rem 0 0;" aria-live="polite"
                     x-text="@js(__('institution.competitions.fields.characters_remaining', ['remaining' => '__remaining__', 'max' => '__max__'])).replace('__remaining__', remaining).replace('__max__', max)">
                    {{ __('institution.competitions.fields.characters_remaining', ['remaining' => 1000 - mb_strlen((string) old('purpose', $competition->purpose)), 'max' => 1000]) }}
                </div>
                <x-institution.input-error :messages="$errors->get('purpose')" />
            </div>
        </div>

        <div style="margin-top: 1.5rem; display: flex; gap: .75rem;">
            <button type="submit" name="action" value="draft" class="ia-btn ia-btn-secondary">{{ __('institution.competitions.save_draft') }}</button>
            <button type="submit" name="action" value="next" class="ia-btn">{{ __('institution.competitions.next_step') }} →</button>
        </div>
    </form>
</x-institution.app-layout>
