<x-institution.app-layout :title="__('institution.nav.competitions')">
    @include('institution.competitions._steps')

    @php
        $locales = array_keys(config('locales.supported'));
        $englishHasErrors = $errors->hasAny(['en.name', 'en.subject', 'en.purpose']);
        $initialLocale = old('_locale', $englishHasErrors ? 'en' : config('locales.default'));
        $requiresEnglish = $competition->requiresEnglishContent();
    @endphp

    @if ($competition->status->value === 'needs_info' && $competition->latest_review_message)
        <div class="ip-alert ip-alert-warning">
            <x-institution.icon name="warning" />
            <div>
                <div class="ip-alert-title">{{ __('institution.competitions.needs_info_title') }}</div>
                <div class="ip-alert-text">{{ $competition->latest_review_message }}</div>
            </div>
        </div>
    @endif

    <form method="POST" action="{{ route('institution.competitions.step.update', [$competition, $step]) }}" novalidate autocomplete="off"
          x-data="{ locale: @js($initialLocale) }">
        @csrf
        @method('PUT')

        <input type="hidden" name="_locale" :value="locale">

        <div class="ip-card">
            <div class="ip-section-title">{{ __('institution.competitions.steps.2.label') }}</div>
            <div class="ip-section-hint">{{ __('institution.competitions.steps.2.hint') }}</div>

            <section aria-labelledby="shared-information-title">
                <h2 id="shared-information-title" class="ip-form-section-title">{{ __('institution.competitions.shared_information_title') }}</h2>
                <div class="ip-grid-2 ip-shared-grid">
                    <div class="ia-field">
                        <x-institution.field-label for="organizing_institution" :value="__('institution.competitions.fields.organizing_institution')" :description="__('institution.competitions.field_help.organizing_institution.description')" :example="$competition->institution->name" />
                        <x-institution.input id="organizing_institution" type="text" :value="$competition->institution->name" readonly aria-readonly="true" />
                        <div class="ip-field-hint">{{ __('institution.competitions.fields.organizing_institution_hint') }}</div>
                    </div>

                    <div class="ia-field">
                        <x-institution.field-label for="partners" :value="__('institution.competitions.fields.partners')" :description="__('institution.competitions.field_help.partners.description')" :example="__('institution.competitions.field_help.partners.example')" />
                        <x-institution.input id="partners" type="text" name="partners" :value="old('partners', $competition->partners)" :placeholder="__('institution.competitions.fields.partners_placeholder')" autocomplete="off" />
                        <div class="ip-field-hint">{{ __('institution.competitions.fields.partners_hint') }}</div>
                        <x-institution.input-error :messages="$errors->get('partners')" />
                    </div>
                </div>
            </section>

            <section class="ip-form-section" aria-labelledby="translated-information-title">
                <div class="ip-form-section-heading">
                    <div>
                        <h2 id="translated-information-title" class="ip-form-section-title">{{ __('institution.competitions.translated_information_title') }}</h2>
                        <p class="ip-form-section-hint">{{ __('institution.competitions.translated_information_hint') }}</p>
                    </div>
                    <span class="ip-audience-language">
                        {{ $requiresEnglish
                            ? __('institution.competitions.language_requirement.international_short')
                            : __('institution.competitions.language_requirement.national_short') }}
                    </span>
                </div>

                <div class="ip-language-requirement">
                    {{ $requiresEnglish
                        ? __('institution.competitions.language_requirement.international')
                        : __('institution.competitions.language_requirement.national') }}
                </div>

                <div class="ip-language-tabs" role="tablist" aria-label="{{ __('institution.competitions.language_tabs_label') }}">
                    @foreach ($locales as $locale)
                        @php
                            $localeIsRequired = $locale === config('locales.default') || ($locale === 'en' && $requiresEnglish);
                            $localeHasErrors = $errors->hasAny(["{$locale}.name", "{$locale}.subject", "{$locale}.purpose"]);
                            $localeIndex = array_search($locale, $locales, true);
                            $nextLocale = $locales[($localeIndex + 1) % count($locales)];
                            $previousLocale = $locales[($localeIndex - 1 + count($locales)) % count($locales)];
                        @endphp
                        <button
                            type="button"
                            id="language-tab-{{ $locale }}"
                            role="tab"
                            x-ref="tab_{{ $locale }}"
                            class="ip-language-tab"
                            :class="locale === @js($locale) ? 'is-active' : ''"
                            :aria-selected="locale === @js($locale)"
                            :tabindex="locale === @js($locale) ? 0 : -1"
                            aria-controls="language-panel-{{ $locale }}"
                            @click="locale = @js($locale)"
                            @keydown.right.prevent="locale = @js($nextLocale); $nextTick(() => $refs['tab_' + locale].focus())"
                            @keydown.left.prevent="locale = @js($previousLocale); $nextTick(() => $refs['tab_' + locale].focus())"
                        >
                            <span>{{ config("locales.supported.$locale") }}</span>
                            @if ($localeHasErrors)
                                <span class="ip-language-status is-error">{{ __('institution.competitions.language_status.error') }}</span>
                            @else
                                <span class="ip-language-status">{{ $localeIsRequired ? __('institution.competitions.language_status.required') : __('institution.competitions.language_status.optional') }}</span>
                            @endif
                        </button>
                    @endforeach
                </div>

                @foreach ($locales as $locale)
                    @php
                        $translation = $competition->getTranslation($locale, false);
                        $nameValue = old("{$locale}.name", $translation?->name);
                        $subjectValue = old("{$locale}.subject", $translation?->subject);
                        $purposeValue = old("{$locale}.purpose", $translation?->purpose);
                    @endphp
                    <div
                        id="language-panel-{{ $locale }}"
                        class="ip-language-panel"
                        role="tabpanel"
                        aria-labelledby="language-tab-{{ $locale }}"
                        x-show="locale === @js($locale)"
                        x-cloak
                    >
                        <div class="ia-field">
                            <x-institution.field-label :for="$locale.'_name'" :value="__('institution.competitions.fields.name')" :description="__('institution.competitions.field_help.name.description')" :example="__('institution.competitions.field_help.name.example')" />
                            <x-institution.input :id="$locale.'_name'" type="text" :name="$locale.'[name]'" :value="$nameValue" autocomplete="off" />
                            <x-institution.input-error :messages="$errors->get($locale.'.name')" />
                        </div>

                        <div class="ia-field" x-data="{ remaining: {{ max(0, 1000 - mb_strlen((string) $subjectValue)) }}, max: 1000 }">
                            <x-institution.field-label :for="$locale.'_subject'" :value="__('institution.competitions.fields.subject')" :description="__('institution.competitions.field_help.subject.description')" :example="__('institution.competitions.field_help.subject.example')" />
                            <textarea id="{{ $locale }}_subject" name="{{ $locale }}[subject]" class="ia-input" rows="5" maxlength="1000" aria-describedby="{{ $locale }}_subject-limit"
                                      x-on:input="remaining = Math.max(0, max - [...$event.target.value].length)">{{ $subjectValue }}</textarea>
                            <div id="{{ $locale }}_subject-limit" class="ip-field-hint" aria-live="polite"
                                 x-text="@js(__('institution.competitions.fields.characters_remaining', ['remaining' => '__remaining__', 'max' => '__max__'])).replace('__remaining__', remaining).replace('__max__', max)">
                                {{ __('institution.competitions.fields.characters_remaining', ['remaining' => 1000 - mb_strlen((string) $subjectValue), 'max' => 1000]) }}
                            </div>
                            <x-institution.input-error :messages="$errors->get($locale.'.subject')" />
                        </div>

                        <div class="ia-field ip-field-last" x-data="{ remaining: {{ max(0, 1000 - mb_strlen((string) $purposeValue)) }}, max: 1000 }">
                            <x-institution.field-label :for="$locale.'_purpose'" :value="__('institution.competitions.fields.purpose')" :description="__('institution.competitions.field_help.purpose.description')" :example="__('institution.competitions.field_help.purpose.example')" />
                            <textarea id="{{ $locale }}_purpose" name="{{ $locale }}[purpose]" class="ia-input" rows="5" maxlength="1000" aria-describedby="{{ $locale }}_purpose-limit"
                                      x-on:input="remaining = Math.max(0, max - [...$event.target.value].length)">{{ $purposeValue }}</textarea>
                            <div id="{{ $locale }}_purpose-limit" class="ip-field-hint" aria-live="polite"
                                 x-text="@js(__('institution.competitions.fields.characters_remaining', ['remaining' => '__remaining__', 'max' => '__max__'])).replace('__remaining__', remaining).replace('__max__', max)">
                                {{ __('institution.competitions.fields.characters_remaining', ['remaining' => 1000 - mb_strlen((string) $purposeValue), 'max' => 1000]) }}
                            </div>
                            <x-institution.input-error :messages="$errors->get($locale.'.purpose')" />
                        </div>
                    </div>
                @endforeach
            </section>
        </div>

        <div class="ip-form-actions">
            <button type="submit" name="action" value="draft" class="ia-btn ia-btn-secondary">{{ __('institution.competitions.save_draft') }}</button>
            <button type="submit" name="action" value="next" class="ia-btn">{{ __('institution.competitions.next_step') }} →</button>
        </div>
    </form>
</x-institution.app-layout>
