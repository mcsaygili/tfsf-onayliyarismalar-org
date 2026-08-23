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

    <form
        method="POST"
        action="{{ route('institution.competitions.step.update', [$competition, $step]) }}"
        novalidate
        autocomplete="off"
        x-data="{
            country: @js(old('country', $competition->country_id)),
            city: @js(old('city', $competition->city_id)),
            cities: @js($cities->map(fn ($city) => ['id' => $city->id, 'name' => $city->official_name])->values()),
            loadingCities: false,
            cityLoadFailed: false,
            citiesUrl: @js(route('institution.competitions.cities', ['country' => '__COUNTRY__'])),
            async loadCities() {
                this.city = '';
                this.cities = [];
                this.cityLoadFailed = false;
                if (!this.country) return;
                this.loadingCities = true;
                try {
                    const response = await fetch(this.citiesUrl.replace('__COUNTRY__', encodeURIComponent(this.country)), {
                        headers: { 'Accept': 'application/json' },
                    });
                    if (!response.ok) throw new Error('city-load-failed');
                    this.cities = await response.json();
                } catch (error) {
                    this.cityLoadFailed = true;
                } finally {
                    this.loadingCities = false;
                }
            },
        }"
    >
        @csrf
        @method('PUT')

        <div class="ip-card ip-card-spaced">
            <div class="ip-section-title">{{ __('institution.competitions.location_information_title') }}</div>
            <div class="ip-section-hint">{{ __('institution.competitions.location_information_hint') }}</div>

            <div class="ip-grid-2">
                <div class="ia-field">
                    <x-institution.field-label
                        for="country"
                        :value="__('institution.competitions.fields.country')"
                        :description="__('institution.competitions.field_help.country.description')"
                        :example="__('institution.competitions.field_help.country.example')"
                    />
                    <select id="country" name="country" class="ia-input" x-model="country" @change="loadCities" @if ($errors->has('country')) aria-invalid="true" aria-describedby="country-error" @endif>
                        <option value="">{{ __('institution.competitions.select_country') }}</option>
                        @foreach ($countries as $country)
                            <option value="{{ $country->id }}">{{ $country->official_name }}</option>
                        @endforeach
                    </select>
                    <x-institution.input-error id="country-error" :messages="$errors->get('country')" />
                </div>

                <div class="ia-field">
                    <x-institution.field-label
                        for="city"
                        :value="__('institution.competitions.fields.city')"
                        :description="__('institution.competitions.field_help.city.description')"
                        :example="__('institution.competitions.field_help.city.example')"
                    />
                    <select id="city" name="city" class="ia-input" x-model="city" :disabled="!country || loadingCities" @if ($errors->has('city')) aria-invalid="true" aria-describedby="city-error" @endif>
                        <option value="" x-text="loadingCities ? @js(__('institution.competitions.loading_cities')) : @js(__('institution.competitions.select_city'))"></option>
                        <template x-for="option in cities" :key="option.id">
                            <option :value="option.id" x-text="option.name"></option>
                        </template>
                    </select>
                    <div class="ip-field-hint" x-show="!country">{{ __('institution.competitions.select_country_first') }}</div>
                    <div class="ip-field-error" x-show="cityLoadFailed">{{ __('institution.competitions.city_load_failed') }}</div>
                    <x-institution.input-error id="city-error" :messages="$errors->get('city')" />
                </div>
            </div>
        </div>

        <div class="ip-card">
            <div class="ip-section-title">{{ __('institution.competitions.participant_approval_title') }}</div>
            <div class="ip-section-hint">{{ __('institution.competitions.participant_approval_hint') }}</div>

            <fieldset class="ia-field ip-field-last">
                <legend class="ip-visually-hidden">{{ __('institution.competitions.fields.participant_approval_process') }}</legend>
                <x-institution.field-label
                    for="participant_approval_process"
                    :group="true"
                    :value="__('institution.competitions.fields.participant_approval_process')"
                    :description="__('institution.competitions.field_help.participant_approval_process.description')"
                    :example="__('institution.competitions.field_help.participant_approval_process.example')"
                />

                @if ($approvalProcesses->isEmpty())
                    <div class="ip-alert ip-alert-warning ip-alert-last">
                        <x-institution.icon name="warning" />
                        <div class="ip-alert-text">{{ __('institution.competitions.no_participant_approval_processes') }}</div>
                    </div>
                @else
                    <div class="ip-choice-options">
                        @foreach ($approvalProcesses as $process)
                            <label class="ip-choice-option" for="participant_approval_process_{{ $process->id }}">
                                <input
                                    id="participant_approval_process_{{ $process->id }}"
                                    type="radio"
                                    name="participant_approval_process"
                                    value="{{ $process->id }}"
                                    @checked(old('participant_approval_process', $competition->participant_approval_process_id) === $process->id)
                                    @if ($errors->has('participant_approval_process')) aria-invalid="true" aria-describedby="participant-approval-process-error" @endif
                                >
                                <span class="ip-choice-content">
                                    <span class="ip-choice-heading"><strong>{{ $process->name }}</strong></span>
                                    <span class="ip-choice-description">{{ $process->description }}</span>
                                </span>
                            </label>
                        @endforeach
                    </div>
                @endif

                <x-institution.input-error id="participant-approval-process-error" :messages="$errors->get('participant_approval_process')" />
            </fieldset>
        </div>

        <div class="ip-form-actions">
            <button type="submit" name="action" value="draft" class="ia-btn ia-btn-secondary">{{ __('institution.competitions.save_draft') }}</button>
            <button type="submit" name="action" value="next" class="ia-btn" @disabled($countries->isEmpty() || $approvalProcesses->isEmpty())>{{ __('institution.competitions.next_step') }} →</button>
        </div>
    </form>
</x-institution.app-layout>
