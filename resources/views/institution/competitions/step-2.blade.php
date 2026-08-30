<x-institution.app-layout :title="__('institution.nav.competitions')">
    @include('institution.competitions._steps')

    @php
        $locales = array_keys(config('locales.supported'));
        $englishHasErrors = $errors->hasAny(['en.name', 'en.subject', 'en.purpose']);
        $initialLocale = old('_locale', $englishHasErrors ? 'en' : config('locales.default'));
        $requiresEnglish = $competition->requiresEnglishContent();
        $dateFields = ['application_starts_at', 'application_ends_at', 'competition_ends_at', 'evaluation_starts_at', 'evaluation_ends_at'];
        $calendarParts = [];
        $calendarValues = [];

        foreach ($dateFields as $dateField) {
            $date = $competition->{$dateField};
            $calendarValues[$dateField] = old($dateField, $date?->format('Y-m-d\TH:i'));
            $calendarParts[$dateField] = [
                'day' => old($dateField.'_day', $date?->format('d')),
                'month' => old($dateField.'_month', $date?->format('m')),
                'year' => old($dateField.'_year', $date?->format('Y')),
                'hour' => old($dateField.'_hour', $date?->format('H')),
                'minute' => old($dateField.'_minute', $date?->format('i')),
            ];
        }
    @endphp

    <form method="POST" action="{{ route('institution.competitions.step.update', [$competition, $step]) }}" novalidate autocomplete="off" data-wizard-form
          x-data="{
              locale: @js($initialLocale),
              partners: @js(collect(explode(',', (string) old('partners', $competition->partners)))->map(fn ($partner) => trim($partner))->filter()->values()),
              partnerInput: '',
              calendar: @js($calendarParts),
              calendarLimits: {
                  day: { min: 1, max: 31 },
                  month: { min: 1, max: 12 },
                  year: { min: 2020, max: 2040 },
                  hour: { min: 0, max: 23 },
                  minute: { min: 0, max: 59 },
              },
              dateTimeValue(field) {
                  const parts = this.calendar[field];
                  const values = ['day', 'month', 'year', 'hour', 'minute'].map(part => String(parts[part] ?? '').trim());
                  if (values.some(value => value === '')) return '';

                  const [day, month, year, hour, minute] = values;
                  return `${year.padStart(4, '0')}-${month.padStart(2, '0')}-${day.padStart(2, '0')}T${hour.padStart(2, '0')}:${minute.padStart(2, '0')}`;
              },
              constrainCalendarPart(field, part, event) {
                  const limits = this.calendarLimits[part];
                  const rawValue = String(event.target.value ?? '');

                  if (rawValue === '') {
                      this.calendar[field][part] = '';
                      return;
                  }

                  const value = Math.min(limits.max, Number.parseInt(rawValue, 10));
                  event.target.value = String(value);
                  this.calendar[field][part] = String(value);
              },
              finalizeCalendarPart(field, part, event) {
                  if (event.target.value === '') return;

                  const limits = this.calendarLimits[part];
                  const value = Math.min(limits.max, Math.max(limits.min, Number.parseInt(event.target.value, 10)));
                  event.target.value = String(value);
                  this.calendar[field][part] = String(value);
              },
              addPartners() {
                  this.partnerInput.split(',').map(value => value.trim()).filter(Boolean).forEach(value => {
                      if (!this.partners.some(existing => existing.toLocaleLowerCase('tr-TR') === value.toLocaleLowerCase('tr-TR'))) this.partners.push(value);
                  });
                  this.partnerInput = '';
              },
          }">
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
                        <x-institution.field-label for="partners_entry" :value="__('institution.competitions.fields.partners')" :description="__('institution.competitions.field_help.partners.description')" :example="__('institution.competitions.field_help.partners.example')" />
                        <input id="partners" type="hidden" name="partners" :value="partners.join(', ')">
                        <div class="ip-token-input" @click="$refs.partnerEntry.focus()">
                            <template x-for="(partner, index) in partners" :key="partner"><span class="ip-token"><span x-text="partner"></span><button type="button" @click.stop="partners.splice(index, 1)" :aria-label="@js(__('institution.competitions.remove_partner')) + ' ' + partner">×</button></span></template>
                            <input id="partners_entry" x-ref="partnerEntry" type="text" x-model="partnerInput" @keydown.enter.prevent="addPartners" @keydown="if ($event.key === ',') { $event.preventDefault(); addPartners(); }" @blur="addPartners" :placeholder="partners.length ? '' : @js(__('institution.competitions.fields.partners_placeholder'))" autocomplete="off">
                        </div>
                        <div class="ip-field-hint">{{ __('institution.competitions.fields.partners_hint') }}</div>
                        <x-institution.input-error :messages="$errors->get('partners')" />
                    </div>
                </div>
            </section>

            <section class="ip-form-section" aria-labelledby="competition-calendar-title">
                <h2 id="competition-calendar-title" class="ip-form-section-title">{{ __('institution.competitions.calendar_title') }}</h2>
                <p class="ip-form-section-hint">{{ __('institution.competitions.calendar_hint') }}</p>
                <div class="ip-grid-3 ip-calendar-grid">
                    @foreach ($dateFields as $dateField)
                        <div class="ia-field ip-field-last">
                            <x-institution.field-label :for="$dateField" :value="__('institution.competitions.fields.'.$dateField)" :description="__('institution.competitions.field_help.'.$dateField.'.description')" :example="__('institution.competitions.field_help.'.$dateField.'.example')" group />
                            <input type="hidden" name="{{ $dateField }}" value="{{ $calendarValues[$dateField] }}" :value="dateTimeValue(@js($dateField))">

                            <div class="ip-date-time-control" role="group" aria-label="{{ __('institution.competitions.fields.'.$dateField) }}">
                                @foreach ([
                                    'date' => [
                                        'separator' => '/',
                                        'parts' => [
                                            'day' => ['min' => 1, 'max' => 31, 'placeholder' => 'GG', 'class' => ''],
                                            'month' => ['min' => 1, 'max' => 12, 'placeholder' => 'AA', 'class' => ''],
                                            'year' => ['min' => 2020, 'max' => 2040, 'placeholder' => 'YYYY', 'class' => 'is-year'],
                                        ],
                                    ],
                                    'time' => [
                                        'separator' => ':',
                                        'parts' => [
                                            'hour' => ['min' => 0, 'max' => 23, 'placeholder' => 'SS', 'class' => ''],
                                            'minute' => ['min' => 0, 'max' => 59, 'placeholder' => 'DD', 'class' => ''],
                                        ],
                                    ],
                                ] as $group => $groupOptions)
                                    <div class="ip-date-time-group is-{{ $group }}">
                                        @foreach ($groupOptions['parts'] as $part => $options)
                                            @php
                                                $partId = $dateField.'_'.$part;
                                            @endphp
                                            @if (! $loop->first)
                                                <span class="ip-date-time-separator" aria-hidden="true">{{ $groupOptions['separator'] }}</span>
                                            @endif
                                            <div class="ip-date-time-part {{ $options['class'] }}">
                                                <label for="{{ $partId }}">{{ __('institution.competitions.calendar_parts.'.$part) }}</label>
                                                <input
                                                    id="{{ $partId }}"
                                                    name="{{ $partId }}"
                                                    type="number"
                                                    class="ip-date-time-input"
                                                    min="{{ $options['min'] }}"
                                                    max="{{ $options['max'] }}"
                                                    step="1"
                                                    inputmode="numeric"
                                                    placeholder="{{ $options['placeholder'] }}"
                                                    value="{{ $calendarParts[$dateField][$part] }}"
                                                    x-model="calendar.{{ $dateField }}.{{ $part }}"
                                                    @keydown="if (['e', 'E', '+', '-', '.', ','].includes($event.key)) $event.preventDefault()"
                                                    @input="constrainCalendarPart(@js($dateField), @js($part), $event)"
                                                    @blur="finalizeCalendarPart(@js($dateField), @js($part), $event)"
                                                    aria-describedby="calendar-format-hint{{ $errors->has($dateField) ? ' '.$dateField.'_error' : '' }}"
                                                    @if ($errors->has($dateField)) aria-invalid="true" @endif
                                                >
                                            </div>
                                        @endforeach
                                    </div>
                                @endforeach
                            </div>
                            <x-institution.input-error :id="$dateField.'_error'" :messages="$errors->get($dateField)" />
                        </div>
                    @endforeach
                </div>
                <p id="calendar-format-hint" class="ip-calendar-format-hint">{{ __('institution.competitions.calendar_numeric_hint') }}</p>
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

        <div class="ip-form-actions ip-form-actions-sticky">
            <span class="ip-save-meta">{{ __('institution.competitions.last_saved_at', ['time' => $competition->updated_at->format('d.m.Y H:i')]) }}</span>
            <button type="submit" name="action" value="draft" class="ia-btn ia-btn-secondary">{{ __('institution.competitions.save_draft') }}</button>
            <button type="submit" name="action" value="next" class="ia-btn">{{ __('institution.competitions.next_step') }} →</button>
        </div>
    </form>
</x-institution.app-layout>
