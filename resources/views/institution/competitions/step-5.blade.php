<x-institution.app-layout :title="__('institution.nav.competitions')">
    @include('institution.competitions._steps')

    <form
        method="POST"
        action="{{ route('institution.competitions.step.update', [$competition, $step]) }}"
        novalidate
        autocomplete="off"
        data-wizard-form
        x-data="{
            regions: @js($regionFormData),
            cityOptions: @js($regionCities),
            loading: {},
            failed: {},
            help: null,
            helpTrigger: null,
            citiesUrl: @js(route('institution.competitions.cities', ['country' => '__COUNTRY__'])),
            addRegion() {
                if (this.regions.length < 20) this.regions.push({ id: '', country: '', city: '' });
            },
            removeRegion(index) {
                if (this.regions.length > 1 && confirm(@js(__('institution.competitions.confirm_remove_region')))) this.regions.splice(index, 1);
            },
            openHelp(title, description, example, trigger) { this.help = { title, description, example }; this.helpTrigger = trigger; this.$nextTick(() => { this.$refs.helpDialog.showModal(); this.$nextTick(() => this.$refs.helpClose?.focus()); }); },
            closeHelp() { if (this.$refs.helpDialog?.open) this.$refs.helpDialog.close(); this.help = null; this.$nextTick(() => this.helpTrigger?.focus()); },
            async loadCities(index) {
                const region = this.regions[index];
                region.city = '';
                this.failed[index] = false;
                if (!region.country) return;
                if (this.cityOptions[region.country]) return;
                this.loading[index] = true;
                try {
                    const response = await fetch(this.citiesUrl.replace('__COUNTRY__', encodeURIComponent(region.country)), { headers: { 'Accept': 'application/json' } });
                    if (!response.ok) throw new Error('city-load-failed');
                    this.cityOptions[region.country] = await response.json();
                } catch (error) {
                    this.failed[index] = true;
                } finally {
                    this.loading[index] = false;
                }
            },
        }"
    >
        @csrf @method('PUT')

        @if ($competition->competitionType?->requires_location)
            <section class="ip-card ip-card-spaced" aria-labelledby="capture-regions-title">
                <div class="ip-category-intro">
                    <div>
                        <h2 id="capture-regions-title" class="ip-section-title">{{ __('institution.competitions.location_information_title') }}</h2>
                        <div class="ip-section-hint">{{ __('institution.competitions.location_information_hint') }}</div>
                    </div>
                    <button type="button" class="ia-btn ia-btn-secondary ip-btn-sm" @click="addRegion" :disabled="regions.length >= 20">+ {{ __('institution.competitions.add_capture_region') }}</button>
                </div>

                <template x-for="(region, index) in regions" :key="region.id || `new-region-${index}`">
                    <div class="ip-region-row">
                        <div class="ip-region-heading">
                            <strong><span x-text="index + 1"></span>. {{ __('institution.competitions.capture_region') }}</strong>
                            <button type="button" class="ip-category-remove" @click="removeRegion(index)" x-show="regions.length > 1">{{ __('institution.competitions.remove_region') }}</button>
                        </div>
                        <input type="hidden" :name="`regions[${index}][id]`" :value="region.id">
                        <div class="ip-grid-2">
                            <div class="ia-field ip-field-last">
                                <div class="ip-field-label-row"><label class="ia-label" :for="`region-${index}-country`">{{ __('institution.competitions.fields.country') }}</label><button type="button" class="ip-field-help-button" @click="openHelp(@js(__('institution.competitions.fields.country')), @js(__('institution.competitions.field_help.country.description')), @js(__('institution.competitions.field_help.country.example')), $el)" aria-haspopup="dialog">?</button></div>
                                <select class="ia-input" :id="`region-${index}-country`" :name="`regions[${index}][country]`" x-model="region.country" @change="loadCities(index)">
                                    <option value="">{{ __('institution.competitions.select_country') }}</option>
                                    @foreach ($countries as $country)<option value="{{ $country->id }}">{{ $country->official_name }}</option>@endforeach
                                </select>
                                <template x-if="@js($errors->has('regions.*.country'))"><div class="ia-error">{{ __('institution.competitions.validation.select_valid_country') }}</div></template>
                            </div>
                            <div class="ia-field ip-field-last">
                                <div class="ip-field-label-row"><label class="ia-label" :for="`region-${index}-city`">{{ __('institution.competitions.fields.city') }}</label><button type="button" class="ip-field-help-button" @click="openHelp(@js(__('institution.competitions.fields.city')), @js(__('institution.competitions.field_help.city.description')), @js(__('institution.competitions.field_help.city.example')), $el)" aria-haspopup="dialog">?</button></div>
                                <select class="ia-input" :id="`region-${index}-city`" :name="`regions[${index}][city]`" x-model="region.city" :disabled="!region.country || loading[index]">
                                    <option value="" x-text="loading[index] ? @js(__('institution.competitions.loading_cities')) : @js(__('institution.competitions.select_city'))"></option>
                                    <template x-for="option in (cityOptions[region.country] || [])" :key="option.id"><option :value="option.id" x-text="option.name"></option></template>
                                </select>
                                <div class="ip-field-hint" x-show="!region.country">{{ __('institution.competitions.select_country_first') }}</div>
                                <div class="ia-error" x-show="failed[index]">{{ __('institution.competitions.city_load_failed') }}</div>
                            </div>
                        </div>
                    </div>
                </template>
                <x-institution.input-error :messages="$errors->get('regions')" />
            </section>
        @endif

        @if ($competition->competitionType?->requires_approval_process)
            <section class="ip-card" aria-labelledby="approval-process-title">
                <h2 id="approval-process-title" class="ip-section-title">{{ __('institution.competitions.participant_approval_title') }}</h2>
                <div class="ip-section-hint">{{ __('institution.competitions.participant_approval_hint') }}</div>
                <fieldset class="ia-field ip-field-last">
                    <legend class="ip-visually-hidden">{{ __('institution.competitions.fields.participant_approval_process') }}</legend>
                    <x-institution.field-label for="participant_approval_process" :group="true" :value="__('institution.competitions.fields.participant_approval_process')" :description="__('institution.competitions.field_help.participant_approval_process.description')" :example="__('institution.competitions.field_help.participant_approval_process.example')" />
                    @if ($approvalProcesses->isEmpty())
                        <div class="ip-alert ip-alert-warning ip-alert-last"><x-institution.icon name="warning" /><div class="ip-alert-text">{{ __('institution.competitions.no_participant_approval_processes') }}</div></div>
                    @else
                        <div class="ip-choice-options">
                            @foreach ($approvalProcesses as $process)
                                <label class="ip-choice-option" for="participant_approval_process_{{ $process->id }}">
                                    <input id="participant_approval_process_{{ $process->id }}" type="radio" name="participant_approval_process" value="{{ $process->id }}" @checked(old('participant_approval_process', $competition->participant_approval_process_id) === $process->id)>
                                    <span class="ip-choice-content"><span class="ip-choice-heading"><strong>{{ $process->name }}</strong></span><span class="ip-choice-description">{{ $process->description }}</span></span>
                                </label>
                            @endforeach
                        </div>
                    @endif
                    <x-institution.input-error id="participant-approval-process-error" :messages="$errors->get('participant_approval_process')" />
                </fieldset>
            </section>
        @endif

        <div class="ip-form-actions ip-form-actions-sticky">
            <span class="ip-save-meta">{{ __('institution.competitions.last_saved_at', ['time' => $competition->updated_at->format('d.m.Y H:i')]) }}</span>
            <button type="submit" name="action" value="draft" class="ia-btn ia-btn-secondary">{{ __('institution.competitions.save_draft') }}</button>
            <button type="submit" name="action" value="next" class="ia-btn" @disabled(($competition->competitionType?->requires_location && $countries->isEmpty()) || ($competition->competitionType?->requires_approval_process && $approvalProcesses->isEmpty()))>{{ __('institution.competitions.next_step') }} →</button>
        </div>

        <dialog class="ip-field-help-dialog ip-native-dialog" x-ref="helpDialog" @click="if ($event.target === $el) closeHelp()" @cancel.prevent="closeHelp()" :aria-label="help?.title">
            <div class="ip-field-help-panel">
                <div class="ip-field-help-header"><h2 x-text="help?.title"></h2><button x-ref="helpClose" type="button" class="ip-field-help-close" @click="closeHelp()" aria-label="{{ __('institution.competitions.close_help') }}">×</button></div>
                <div class="ip-field-help-body">
                    <p class="ip-field-help-description" x-text="help?.description"></p>
                    <div class="ip-field-help-example" x-show="help?.example"><strong>{{ __('institution.competitions.help_example') }}</strong><span x-text="help?.example"></span></div>
                </div>
                <div class="ip-field-help-footer"><button type="button" class="ia-btn ia-btn-secondary ip-field-help-dismiss" @click="closeHelp()">{{ __('institution.field_help.done') }}</button></div>
            </div>
        </dialog>
    </form>
</x-institution.app-layout>
