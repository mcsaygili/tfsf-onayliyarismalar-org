<x-institution.app-layout :title="__('institution.nav.competitions')">
    @include('institution.competitions._steps')

    @php
        $categoryErrorLocales = [];
        foreach ($errors->keys() as $errorKey) {
            if (preg_match('/^categories\.(\d+)\.(tr|en)\./', $errorKey, $matches)) {
                $categoryErrorLocales[(int) $matches[1]] = $matches[2];
            } elseif (preg_match('/^categories\.(\d+)\./', $errorKey, $matches)) {
                $categoryErrorLocales[(int) $matches[1]] ??= 'tr';
            }
        }
        $noMembershipCheckId = $memberGroups->firstWhere('code', 'no-membership-check')?->id;
        $noDeviceCheckId = $captureDevices->firstWhere('code', 'no-device-check')?->id;
        $noProcessingCheckId = $processingMethods->firstWhere('code', 'no-processing-check')?->id;
    @endphp

    <form
        method="POST"
        action="{{ route('institution.competitions.step.update', [$competition, $step]) }}"
        novalidate
        data-wizard-form
        x-data="{
            categories: @js($categoryFormData),
            help: null,
            init() {
                this.categories = this.categories.map(category => ({
                    id: category.id || '', tr: category.tr || { name: '' }, en: category.en || { name: '' },
                    locale: category.locale || 'tr', expanded: category.expanded ?? true,
                    age_eligibility_rule: category.age_eligibility_rule || '', gender_id: category.gender_id || '',
                    member_group_match_mode: category.member_group_match_mode || 'any',
                    member_group_ids: category.member_group_ids || [], capture_device_ids: category.capture_device_ids || [], processing_method_ids: category.processing_method_ids || [],
                }));
                const errorLocales = @js($categoryErrorLocales);
                Object.entries(errorLocales).forEach(([index, locale]) => {
                    if (this.categories[index]) { this.categories[index].locale = locale; this.categories[index].expanded = true; }
                });
            },
            addCategory() {
                if (this.categories.length >= 20) return;
                this.categories.push({ id: '', tr: { name: '' }, en: { name: '' }, locale: 'tr', expanded: true, age_eligibility_rule: '', gender_id: '', member_group_match_mode: 'any', member_group_ids: [], capture_device_ids: [], processing_method_ids: [] });
            },
            duplicateCategory(index) {
                if (this.categories.length >= 20) return;
                const duplicate = JSON.parse(JSON.stringify(this.categories[index]));
                duplicate.id = ''; duplicate.tr.name += ' ' + @js(__('institution.competitions.copy_suffix')); if (duplicate.en.name) duplicate.en.name += ' Copy'; duplicate.expanded = true;
                this.categories.splice(index + 1, 0, duplicate);
            },
            moveCategory(index, direction) { const target = index + direction; if (target < 0 || target >= this.categories.length) return; [this.categories[index], this.categories[target]] = [this.categories[target], this.categories[index]]; },
            removeCategory(index) { if (this.categories.length > 1 && confirm(@js(__('institution.competitions.confirm_remove_category')))) this.categories.splice(index, 1); },
            categoryTitle(category, index) { return (category.tr.name || '').trim() || `${@js(__('institution.competitions.category'))} ${index + 1}`; },
            missingCount(category) { return [category.tr.name, category.age_eligibility_rule, category.gender_id, category.member_group_ids.length, category.capture_device_ids.length, category.processing_method_ids.length].filter(value => !value || value.length === 0).length; },
            toggleExclusive(values, changed, exclusive) { if (!exclusive) return; if (changed === exclusive && values.includes(exclusive)) values.splice(0, values.length, exclusive); else if (changed !== exclusive && values.includes(changed)) { const at = values.indexOf(exclusive); if (at >= 0) values.splice(at, 1); } },
            helpTrigger: null,
            openHelp(title, description, example = '', trigger = null) { this.help = { title, description, example }; this.helpTrigger = trigger; this.$nextTick(() => { this.$refs.helpDialog?.showModal(); this.$nextTick(() => this.$refs.helpClose?.focus()); }); },
            closeHelp() { if (this.$refs.helpDialog?.open) this.$refs.helpDialog.close(); this.help = null; this.$nextTick(() => this.helpTrigger?.focus()); },
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
                        <button type="button" class="ip-category-toggle" @click="category.expanded = !category.expanded" :aria-expanded="category.expanded.toString()">
                            <span class="ip-category-number" x-text="index + 1"></span>
                            <span><strong x-text="categoryTitle(category, index)"></strong><small x-show="missingCount(category)" x-text="missingCount(category) + ' ' + @js(__('institution.competitions.missing_fields'))"></small></span>
                        </button>
                        <div class="ip-category-tools">
                            <button type="button" @click="moveCategory(index, -1)" :disabled="index === 0" aria-label="{{ __('institution.competitions.move_category_up') }}">↑</button>
                            <button type="button" @click="moveCategory(index, 1)" :disabled="index === categories.length - 1" aria-label="{{ __('institution.competitions.move_category_down') }}">↓</button>
                            <button type="button" @click="duplicateCategory(index)" :disabled="categories.length >= 20">{{ __('institution.competitions.duplicate_category') }}</button>
                            <button type="button" class="ip-category-remove" @click="removeCategory(index)" x-show="categories.length > 1">{{ __('institution.competitions.remove_category') }}</button>
                        </div>
                    </div>
                    <input type="hidden" :name="`categories[${index}][id]`" :value="category.id">

                    <div x-show="category.expanded">

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
                            <div class="ip-field-label-row"><label class="ia-label" :for="`category-${index}-tr-name`">{{ __('institution.competitions.category_name_tr') }}</label><button type="button" class="ip-field-help-button" @click="openHelp(@js(__('institution.competitions.category_name_tr')), @js(__('institution.competitions.field_help.category_name.description')), @js(__('institution.competitions.field_help.category_name.example')), $el)" aria-haspopup="dialog">?</button></div>
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
                            <div class="ip-field-label-row"><label class="ia-label" :for="`category-${index}-en-name`">{{ __('institution.competitions.category_name_en') }}</label><button type="button" class="ip-field-help-button" @click="openHelp(@js(__('institution.competitions.category_name_en')), @js(__('institution.competitions.field_help.category_name_en.description')), @js(__('institution.competitions.field_help.category_name_en.example')), $el)" aria-haspopup="dialog">?</button></div>
                            <input class="ia-input" type="text" maxlength="255" :id="`category-${index}-en-name`" :name="`categories[${index}][en][name]`" x-model="category.en.name">
                        </div>
                    </div>

                    <div class="ip-category-section">
                        <h3>{{ __('institution.competitions.participant_information_title') }}</h3>
                        <fieldset class="ia-field">
                            <legend class="ip-visually-hidden">{{ __('institution.competitions.fields.genders') }}</legend>
                            <div class="ip-field-label-row"><span class="ia-label">{{ __('institution.competitions.fields.genders') }}</span><button type="button" class="ip-field-help-button" @click="openHelp(@js(__('institution.competitions.fields.genders')), @js(__('institution.competitions.field_help.genders.description')), @js(__('institution.competitions.field_help.genders.example')), $el)" aria-haspopup="dialog">?</button></div>
                            <div class="ip-reference-options">
                                @foreach ($genders as $gender)
                                    <label class="ip-reference-option"><input type="radio" :name="`categories[${index}][gender_id]`" value="{{ $gender->id }}" x-model="category.gender_id"><span><strong>{{ $gender->name }}</strong><small>{{ $gender->description }}</small></span></label>
                                @endforeach
                            </div>
                        </fieldset>

                        <fieldset class="ia-field">
                            <legend class="ip-visually-hidden">{{ __('institution.competitions.fields.birth_date') }}</legend>
                            <div class="ip-field-label-row"><span class="ia-label">{{ __('institution.competitions.fields.birth_date') }}</span><button type="button" class="ip-field-help-button" @click="openHelp(@js(__('institution.competitions.fields.birth_date')), @js(__('institution.competitions.field_help.birth_date.description')), @js(__('institution.competitions.field_help.birth_date.example')), $el)" aria-haspopup="dialog">?</button></div>
                            <p class="ip-age-basis-note">{{ __('institution.competitions.age_basis_note') }}</p>
                            <div class="ip-reference-options">
                                @foreach ($ageRules as $ageRule)
                                    <label class="ip-reference-option"><input type="radio" :name="`categories[${index}][age_eligibility_rule]`" value="{{ $ageRule->id }}" x-model="category.age_eligibility_rule"><span><strong>{{ $ageRule->name }}</strong><small>{{ $ageRule->description }}</small></span></label>
                                @endforeach
                            </div>
                        </fieldset>

                        <fieldset class="ia-field ip-field-last">
                            <legend class="ip-visually-hidden">{{ __('institution.competitions.fields.member_groups') }}</legend>
                            <div class="ip-field-label-row"><span class="ia-label">{{ __('institution.competitions.fields.member_groups') }}</span><button type="button" class="ip-field-help-button" @click="openHelp(@js(__('institution.competitions.fields.member_groups')), @js(__('institution.competitions.field_help.member_groups.description')), @js(__('institution.competitions.field_help.member_groups.example')), $el)" aria-haspopup="dialog">?</button></div>
                            <div class="ip-match-mode" x-show="!category.member_group_ids.includes(@js($noMembershipCheckId))">
                                <span>{{ __('institution.competitions.member_group_match_label') }}</span>
                                <label><input type="radio" :name="`categories[${index}][member_group_match_mode]`" value="any" x-model="category.member_group_match_mode" :disabled="category.member_group_ids.includes(@js($noMembershipCheckId))"> {{ __('institution.competitions.match_any') }}</label>
                                <label><input type="radio" :name="`categories[${index}][member_group_match_mode]`" value="all" x-model="category.member_group_match_mode" :disabled="category.member_group_ids.includes(@js($noMembershipCheckId))"> {{ __('institution.competitions.match_all') }}</label>
                            </div>
                            <input type="hidden" :name="`categories[${index}][member_group_match_mode]`" value="any" :disabled="!category.member_group_ids.includes(@js($noMembershipCheckId))">
                            <div class="ip-reference-options">@foreach ($memberGroups as $group)<label class="ip-reference-option"><input type="checkbox" :name="`categories[${index}][member_group_ids][]`" value="{{ $group->id }}" x-model="category.member_group_ids" @change="toggleExclusive(category.member_group_ids, @js($group->id), @js($noMembershipCheckId))"><span><strong>{{ $group->name }}</strong><small>{{ $group->description }}</small></span></label>@endforeach</div>
                        </fieldset>
                    </div>

                    <div class="ip-category-section">
                        <h3>{{ __('institution.competitions.device_information_title') }}</h3>
                        <fieldset class="ia-field ip-field-last">
                            <legend class="ip-visually-hidden">{{ __('institution.competitions.fields.capture_devices') }}</legend>
                            <div class="ip-field-label-row"><span class="ia-label">{{ __('institution.competitions.fields.capture_devices') }}</span><button type="button" class="ip-field-help-button" @click="openHelp(@js(__('institution.competitions.fields.capture_devices')), @js(__('institution.competitions.field_help.capture_devices.description')), @js(__('institution.competitions.field_help.capture_devices.example')), $el)" aria-haspopup="dialog">?</button></div>
                            <div class="ip-reference-options">@foreach ($captureDevices as $device)<label class="ip-reference-option"><input type="checkbox" :name="`categories[${index}][capture_device_ids][]`" value="{{ $device->id }}" x-model="category.capture_device_ids" @change="toggleExclusive(category.capture_device_ids, @js($device->id), @js($noDeviceCheckId))"><span><strong>{{ $device->name }}</strong><small>{{ $device->description }}</small></span></label>@endforeach</div>
                        </fieldset>
                    </div>

                    <div class="ip-category-section">
                        <h3>{{ __('institution.competitions.processing_information_title') }}</h3>
                        <fieldset class="ia-field ip-field-last">
                            <legend class="ip-visually-hidden">{{ __('institution.competitions.fields.processing_methods') }}</legend>
                            <div class="ip-field-label-row"><span class="ia-label">{{ __('institution.competitions.fields.processing_methods') }}</span><button type="button" class="ip-field-help-button" @click="openHelp(@js(__('institution.competitions.fields.processing_methods')), @js(__('institution.competitions.field_help.processing_methods.description')), @js(__('institution.competitions.field_help.processing_methods.example')), $el)" aria-haspopup="dialog">?</button></div>
                            <div class="ip-reference-options">@foreach ($processingMethods as $method)<label class="ip-reference-option"><input type="checkbox" :name="`categories[${index}][processing_method_ids][]`" value="{{ $method->id }}" x-model="category.processing_method_ids" @change="toggleExclusive(category.processing_method_ids, @js($method->id), @js($noProcessingCheckId))"><span><strong>{{ $method->name }}</strong><small>{{ $method->description }}</small></span></label>@endforeach</div>
                        </fieldset>
                    </div>
                    </div>
                </section>
            </template>
        </div>

        <div class="ip-form-actions ip-form-actions-sticky"><span class="ip-save-meta">{{ __('institution.competitions.last_saved_at', ['time' => $competition->updated_at->format('d.m.Y H:i')]) }}</span><button type="submit" name="action" value="draft" class="ia-btn ia-btn-secondary">{{ __('institution.competitions.save_draft') }}</button><button type="submit" name="action" value="next" class="ia-btn">{{ __('institution.competitions.next_step') }} →</button></div>

        <dialog class="ip-field-help-dialog ip-native-dialog" x-ref="helpDialog" @click="if ($event.target === $el) closeHelp()" @cancel.prevent="closeHelp()" :aria-label="help?.title">
            <section class="ip-field-help-panel">
                <div class="ip-field-help-header"><h2 x-text="help?.title"></h2><button x-ref="helpClose" type="button" class="ip-field-help-close" @click="closeHelp()" aria-label="{{ __('institution.competitions.close_help') }}">×</button></div>
                <div class="ip-field-help-body">
                    <p class="ip-field-help-description" x-text="help?.description"></p>
                    <div class="ip-field-help-example" x-show="help?.example"><strong>{{ __('institution.competitions.help_example') }}</strong><span x-text="help?.example"></span></div>
                </div>
                <div class="ip-field-help-footer"><button type="button" class="ia-btn ia-btn-secondary ip-field-help-dismiss" @click="closeHelp()">{{ __('institution.field_help.done') }}</button></div>
            </section>
        </dialog>
    </form>
</x-institution.app-layout>
