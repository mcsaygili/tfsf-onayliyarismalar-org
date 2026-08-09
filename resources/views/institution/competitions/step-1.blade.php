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
            <div class="ip-section-title">{{ __('institution.competitions.steps.1.label') }}</div>
            <div class="ip-section-hint">{{ __('institution.competitions.steps.1.hint') }}</div>

            <div class="ia-field">
                <x-institution.label for="name" :value="__('institution.competitions.fields.name')" />
                <x-institution.input id="name" type="text" name="name" :value="old('name', $competition->name)" autocomplete="off" />
                <x-institution.input-error :messages="$errors->get('name')" />
            </div>

            <div class="ia-field">
                <x-institution.label for="partners" :value="__('institution.competitions.fields.partners')" />
                <textarea id="partners" name="partners" class="ia-input" rows="3">{{ old('partners', $competition->partners) }}</textarea>
                <x-institution.input-error :messages="$errors->get('partners')" />
            </div>

            <div class="ia-field">
                <x-institution.label for="subject" :value="__('institution.competitions.fields.subject')" />
                <textarea id="subject" name="subject" class="ia-input" rows="4">{{ old('subject', $competition->subject) }}</textarea>
                <x-institution.input-error :messages="$errors->get('subject')" />
            </div>

            <div class="ia-field" style="margin-bottom: 0;">
                <x-institution.label for="purpose" :value="__('institution.competitions.fields.purpose')" />
                <textarea id="purpose" name="purpose" class="ia-input" rows="4">{{ old('purpose', $competition->purpose) }}</textarea>
                <x-institution.input-error :messages="$errors->get('purpose')" />
            </div>
        </div>

        <div style="margin-top: 1.5rem; display: flex; gap: .75rem;">
            <button type="submit" name="action" value="draft" class="ia-btn ia-btn-secondary">{{ __('institution.competitions.save_draft') }}</button>
            <button type="submit" name="action" value="next" class="ia-btn">{{ __('institution.competitions.next_step') }} →</button>
        </div>
    </form>
</x-institution.app-layout>
