<x-institution.app-layout :title="__('institution.competitions.steps.7.label')">
    @include('institution.competitions._steps')

    <div class="ip-card ip-card-spaced">
        <div class="ip-section-title">{{ __('institution.competitions.regulation_title') }}</div>
        <p class="ip-section-hint">{{ __('institution.competitions.regulation_hint') }}</p>

        <form method="POST" action="{{ route('institution.competitions.step.update', [$competition, 7]) }}" data-wizard-form>
            @csrf
            @method('PUT')

            @forelse ($editableRegulationItems as $item)
                <section class="ip-regulation-input">
                    <h3>{{ $item->section?->getTranslation()?->name }}</h3>
                    @foreach ($competition->requiresEnglishContent() ? ['tr', 'en'] : ['tr'] as $locale)
                        <div class="ia-field">
                            <x-institution.field-label
                                :for="'regulation_'.$item->id.'_'.$locale"
                                :value="($locale === 'tr' ? 'Türkçe' : 'English').' — '.($item->getTranslation($locale, false)?->content ?? $item->code)"
                                :description="__('institution.competitions.field_help.regulation_input.description')"
                                :example="__('institution.competitions.field_help.regulation_input.example')"
                            />
                            <textarea id="regulation_{{ $item->id }}_{{ $locale }}" name="regulation_inputs[{{ $item->id }}][{{ $locale }}]" class="ia-input" rows="5" maxlength="5000">{{ old("regulation_inputs.{$item->id}.{$locale}", $competition->regulationInputs->first(fn ($input) => $input->regulation_item_id === $item->id && $input->locale === $locale)?->content) }}</textarea>
                            <x-institution.input-error :messages="$errors->get('regulation_inputs.'.$item->id.'.'.$locale)" />
                        </div>
                    @endforeach
                </section>
            @empty
                <div class="ip-alert ip-alert-info">{{ __('institution.competitions.no_regulation_inputs') }}</div>
            @endforelse

            <div class="ip-form-actions ip-form-actions-sticky">
                <span class="ip-save-meta">{{ __('institution.competitions.last_saved_at', ['time' => $competition->updated_at->format('d.m.Y H:i')]) }}</span>
                <button type="submit" name="action" value="draft" class="ia-btn ia-btn-secondary">{{ __('institution.competitions.save_draft') }}</button>
                <button type="submit" name="action" value="next" class="ia-btn">{{ __('institution.competitions.next_step') }} →</button>
            </div>
        </form>
    </div>

    <div class="ip-card">
        <div class="ip-section-title">{{ __('institution.competitions.regulation_preview') }}</div>
        <p class="ip-section-hint">{{ __('institution.competitions.regulation_preview_hint') }}</p>

        <div x-data="{ locale: 'tr' }">
            @if ($competition->requiresEnglishContent())
                <div class="ip-language-tabs" role="tablist">
                    @foreach (['tr' => 'Türkçe', 'en' => 'English'] as $locale => $label)
                        <button type="button" class="ip-language-tab" :class="{ 'is-active': locale === @js($locale) }" @click="locale = @js($locale)" role="tab" :aria-selected="locale === @js($locale)">{{ $label }}</button>
                    @endforeach
                </div>
            @endif

            @foreach ($regulationPreview as $locale => $sections)
                <div x-show="locale === @js($locale)" x-cloak class="ip-regulation-preview">
                    @forelse ($sections as $section)
                        <section>
                            <h3>{{ $section['title'] }}</h3>
                            @foreach ($section['items'] as $item)
                                <p>{!! nl2br(e($item['content'])) !!}</p>
                            @endforeach
                        </section>
                    @empty
                        <p class="ip-section-hint">{{ __('institution.competitions.regulation_preview_empty') }}</p>
                    @endforelse
                </div>
            @endforeach
        </div>
    </div>
</x-institution.app-layout>
