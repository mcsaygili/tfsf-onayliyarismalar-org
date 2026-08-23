<x-institution.app-layout :title="__('institution.nav.competitions')">
    @include('institution.competitions._steps')

    <form
        method="POST"
        action="{{ route('institution.competitions.step.update', [$competition, $step]) }}"
        novalidate
        x-data="{
            categories: @js($categoryFormData),
            help: null,
            init() {
                this.categories = this.categories.map(category => ({
                    id: category.id || '', tr: category.tr || { name: '' }, en: category.en || { name: '' },
                    locale: category.locale || 'tr',
                    age_eligibility_rule: category.age_eligibility_rule || '', gender_id: category.gender_id || '',
                    member_group_ids: category.member_group_ids || [], capture_device_ids: category.capture_device_ids || [],
                }));
            },
            addCategory() {
                if (this.categories.length >= 20) return;
                this.categories.push({ id: '', tr: { name: '' }, en: { name: '' }, locale: 'tr', age_eligibility_rule: '', gender_id: '', member_group_ids: [], capture_device_ids: [] });
            },
            openHelp(title, description, example = '') { this.help = { title, description, example }; this.$nextTick(() => this.$refs.helpClose?.focus()); },
            closeHelp() { this.help = null; },
        }"
        @keydown.escape.window="closeHelp()"
    >
        @csrf @method('PUT')

        @if ($errors->any())
            <div class="ip-alert ip-alert-warning ip-category-errors"><x-institution.icon name="warning" /><div><div class="ip-alert-title">{{ __('institution.competitions.validation.category_errors_title') }}</div><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div></div>
        @endif

        <div class="ip-card">
            <div class="ip-category-intro">
                <div>
                    <div class="ip-category-title-row"><div class="ip-section-title">{{ __('institution.competitions.category_information_title') }}</div><span class="ip-category-count"><strong x-text="categories.length"></strong> <span x-text="categories.length === 1 ? @js(__('institution.competitions.category_count_singular')) : @js(__('institution.competitions.category_count_plural'))"></span></span></div>
                    <div class="ip-section-hint">{{ __('institution.competitions.category_information_hint') }}</div>
                </div>
                <button type="button" class="ia-btn ia-btn-secondary" @click="addCategory" :disabled="categories.length >= 20">+ {{ __('institution.competitions.add_category') }}</button>
            </div>

            <template x-for="(category, index) in categories" :key="category.id || `new-${index}`">
                <section class="ip-category-block">
                    <div class="ip-category-heading">
                        <div><span class="ip-category-number" x-text="index + 1"></span><strong>{{ __('institution.competitions.category') }}</strong></div>
                        <button type="button" class="ip-category-remove" @click="categories.splice(index, 1)" x-show="categories.length > 1">{{ __('institution.competitions.remove_category') }}</button>
                    </div>
                    <input type="hidden" :name="`categories[${index}][id]`" :value="category.id">

                    <div class="ip-language-tabs" role="tablist" :aria-label="@js(__('institution.competitions.category_name'))">
                        <button
                            type="button"
                            role="tab"
                            class="ip-language-tab"
                            :id="`category-${index}-language-tab-tr`"
                            :class="category.locale === 'tr' ? 'is-active' : ''"
                            :aria-selected="category.locale === 'tr'"
                            :tabindex="category.locale === 'tr' ? 0 : -1"
                            :aria-controls="`category-${index}-language-panel-tr`"
                            @click="category.locale = 'tr'"
                            @keydown.right.prevent="category.locale = 'en'; $nextTick(() => $el.nextElementSibling?.focus())"
                            @keydown.left.prevent="category.locale = 'en'; $nextTick(() => $el.nextElementSibling?.focus())"
                        >
                            Türkçe <span class="ip-language-status">{{ __('institution.competitions.language_status.required') }}</span>
                        </button>
                        <button
                            type="button"
                            role="tab"
                            class="ip-language-tab"
                            :id="`category-${index}-language-tab-en`"
                            :class="category.locale === 'en' ? 'is-active' : ''"
                            :aria-selected="category.locale === 'en'"
                            :tabindex="category.locale === 'en' ? 0 : -1"
                            :aria-controls="`category-${index}-language-panel-en`"
                            @click="category.locale = 'en'"
                            @keydown.right.prevent="category.locale = 'tr'; $nextTick(() => $el.previousElementSibling?.focus())"
                            @keydown.left.prevent="category.locale = 'tr'; $nextTick(() => $el.previousElementSibling?.focus())"
                        >
                            English <span class="ip-language-status">{{ $competition->requiresEnglishContent() ? __('institution.competitions.language_status.required') : __('institution.competitions.language_status.optional') }}</span>
                        </button>
                    </div>

                    <div
                        class="ip-language-panel ip-category-name-panel"
                        role="tabpanel"
                        :id="`category-${index}-language-panel-tr`"
                        :aria-labelledby="`category-${index}-language-tab-tr`"
                        x-show="category.locale === 'tr'"
                        x-cloak
                    >
                        <div class="ia-field ip-field-last">
                            <div class="ip-field-label-row"><label class="ia-label" :for="`category-${index}-tr-name`">{{ __('institution.competitions.category_name_tr') }}</label><button type="button" class="ip-field-help-button" @click="openHelp(@js(__('institution.competitions.category_name_tr')), @js(__('institution.competitions.field_help.category_name.description')), @js(__('institution.competitions.field_help.category_name.example')))" aria-haspopup="dialog">?</button></div>
                            <input class="ia-input" type="text" maxlength="255" :id="`category-${index}-tr-name`" :name="`categories[${index}][tr][name]`" x-model="category.tr.name">
                        </div>
                    </div>
                    <div
                        class="ip-language-panel ip-category-name-panel"
                        role="tabpanel"
                        :id="`category-${index}-language-panel-en`"
                        :aria-labelledby="`category-${index}-language-tab-en`"
                        x-show="category.locale === 'en'"
                        x-cloak
                    >
                        <div class="ia-field ip-field-last">
                            <div class="ip-field-label-row"><label class="ia-label" :for="`category-${index}-en-name`">{{ __('institution.competitions.category_name_en') }}</label><button type="button" class="ip-field-help-button" @click="openHelp(@js(__('institution.competitions.category_name_en')), @js(__('institution.competitions.field_help.category_name_en.description')), @js(__('institution.competitions.field_help.category_name_en.example')))" aria-haspopup="dialog">?</button></div>
                            <input class="ia-input" type="text" maxlength="255" :id="`category-${index}-en-name`" :name="`categories[${index}][en][name]`" x-model="category.en.name">
                        </div>
                    </div>

                    <div class="ip-category-section">
                        <h3>{{ __('institution.competitions.participant_information_title') }}</h3>
                        <fieldset class="ia-field">
                            <legend class="ip-visually-hidden">{{ __('institution.competitions.fields.genders') }}</legend>
                            <div class="ip-field-label-row"><span class="ia-label">{{ __('institution.competitions.fields.genders') }}</span><button type="button" class="ip-field-help-button" @click="openHelp(@js(__('institution.competitions.fields.genders')), @js(__('institution.competitions.field_help.genders.description')), @js(__('institution.competitions.field_help.genders.example')))" aria-haspopup="dialog">?</button></div>
                            <div class="ip-reference-options">
                                @foreach ($genders as $gender)
                                    <label class="ip-reference-option"><input type="radio" :name="`categories[${index}][gender_id]`" value="{{ $gender->id }}" x-model="category.gender_id"><span><strong>{{ $gender->name }}</strong><small>{{ $gender->description }}</small></span></label>
                                @endforeach
                            </div>
                        </fieldset>

                        <fieldset class="ia-field">
                            <legend class="ip-visually-hidden">{{ __('institution.competitions.fields.birth_date') }}</legend>
                            <div class="ip-field-label-row"><span class="ia-label">{{ __('institution.competitions.fields.birth_date') }}</span><button type="button" class="ip-field-help-button" @click="openHelp(@js(__('institution.competitions.fields.birth_date')), @js(__('institution.competitions.field_help.birth_date.description')), @js(__('institution.competitions.field_help.birth_date.example')))" aria-haspopup="dialog">?</button></div>
                            <p class="ip-age-basis-note">{{ __('institution.competitions.age_basis_note') }}</p>
                            <div class="ip-reference-options">
                                @foreach ($ageRules as $ageRule)
                                    <label class="ip-reference-option"><input type="radio" :name="`categories[${index}][age_eligibility_rule]`" value="{{ $ageRule->id }}" x-model="category.age_eligibility_rule"><span><strong>{{ $ageRule->name }}</strong><small>{{ $ageRule->description }}</small></span></label>
                                @endforeach
                            </div>
                        </fieldset>

                        <fieldset class="ia-field ip-field-last">
                            <legend class="ip-visually-hidden">{{ __('institution.competitions.fields.member_groups') }}</legend>
                            <div class="ip-field-label-row"><span class="ia-label">{{ __('institution.competitions.fields.member_groups') }}</span><button type="button" class="ip-field-help-button" @click="openHelp(@js(__('institution.competitions.fields.member_groups')), @js(__('institution.competitions.field_help.member_groups.description')), @js(__('institution.competitions.field_help.member_groups.example')))" aria-haspopup="dialog">?</button></div>
                            <div class="ip-reference-options">@foreach ($memberGroups as $group)<label class="ip-reference-option"><input type="checkbox" :name="`categories[${index}][member_group_ids][]`" value="{{ $group->id }}" x-model="category.member_group_ids"><span><strong>{{ $group->name }}</strong><small>{{ $group->description }}</small></span></label>@endforeach</div>
                        </fieldset>
                    </div>

                    <div class="ip-category-section">
                        <h3>{{ __('institution.competitions.device_information_title') }}</h3>
                        <fieldset class="ia-field ip-field-last">
                            <legend class="ip-visually-hidden">{{ __('institution.competitions.fields.capture_devices') }}</legend>
                            <div class="ip-field-label-row"><span class="ia-label">{{ __('institution.competitions.fields.capture_devices') }}</span><button type="button" class="ip-field-help-button" @click="openHelp(@js(__('institution.competitions.fields.capture_devices')), @js(__('institution.competitions.field_help.capture_devices.description')), @js(__('institution.competitions.field_help.capture_devices.example')))" aria-haspopup="dialog">?</button></div>
                            <div class="ip-reference-options">@foreach ($captureDevices as $device)<label class="ip-reference-option"><input type="checkbox" :name="`categories[${index}][capture_device_ids][]`" value="{{ $device->id }}" x-model="category.capture_device_ids"><span><strong>{{ $device->name }}</strong><small>{{ $device->description }}</small></span></label>@endforeach</div>
                        </fieldset>
                    </div>
                </section>
            </template>
        </div>

        <div class="ip-form-actions"><button type="submit" name="action" value="draft" class="ia-btn ia-btn-secondary">{{ __('institution.competitions.save_draft') }}</button><button type="submit" name="action" value="next" class="ia-btn">{{ __('institution.competitions.next_step') }} →</button></div>

        <div class="ip-field-help-overlay" x-show="help" x-cloak @click.self="closeHelp()" role="presentation">
            <section class="ip-field-help-dialog" role="dialog" aria-modal="true" :aria-label="help?.title">
                <div class="ip-field-help-header"><h2 x-text="help?.title"></h2><button x-ref="helpClose" type="button" class="ip-field-help-close" @click="closeHelp()" aria-label="{{ __('institution.competitions.close_help') }}">×</button></div>
                <p class="ip-field-help-description" x-text="help?.description"></p>
                <div class="ip-field-help-example" x-show="help?.example"><strong>{{ __('institution.competitions.help_example') }}</strong><span x-text="help?.example"></span></div>
            </section>
        </div>
    </form>
</x-institution.app-layout>
